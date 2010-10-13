<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
*/

namespace Doctrine\DBAL\Migrations;

use Doctrine\DBAL\Migrations\Configuration\Configuration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Class which wraps a migration version and allows execution of the
 * individual migration version up or down method.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Version
{
    const STATE_NONE = 0;
    const STATE_PRE  = 1;
    const STATE_EXEC = 2;
    const STATE_POST = 3;

    /**
     * The Migrations Configuration instance for this migration
     *
     * @var Configuration
     */
    private $_configuration;

    /**
     * The OutputWriter object instance used for outputting information
     *
     * @var OutputWriter
     */
    private $_outputWriter;

    /**
     * The version in timestamp format (YYYYMMDDHHMMSS)
     *
     * @param int
     */
    private $_version;

    /**
     * @var AbstractSchemaManager
     */
    private $_sm;

    /**
     * @var AbstractPlatform
     */
    private $_platform;

    /**
     * The migration instance for this version
     *
     * @var AbstractMigration
     */
    private $_migration;

    /**
     * @var Connection
     */
    private $_connection;

    /**
     * @var string
     */
    private $_class;

    /** The array of collected SQL statements for this version */
    private $_sql = array();

    /** The time in seconds that this migration version took to execute */
    private $_time;

    /**
     * @var int
     */
    private $_state = self::STATE_NONE;

    public function __construct(Configuration $configuration, $version, $class)
    {
        $this->_configuration = $configuration;
        $this->_outputWriter = $configuration->getOutputWriter();
        $this->_version = $version;
        $this->_class = $class;
        $this->_connection = $configuration->getConnection();
        $this->_sm = $this->_connection->getSchemaManager();
        $this->_platform = $this->_connection->getDatabasePlatform();
        $this->_migration = new $class($this);
    }

    /**
     * Returns the string version in the format YYYYMMDDHHMMSS
     *
     * @return string $version
     */
    public function getVersion()
    {
        return $this->_version;
    }

    /**
     * Returns the Migrations Configuration object instance
     *
     * @return Configuration $configuration
     */
    public function getConfiguration()
    {
        return $this->_configuration;
    }

    /**
     * Check if this version has been migrated or not.
     *
     * @param bool $bool
     * @return mixed
     */
    public function isMigrated()
    {
        return $this->_configuration->hasVersionMigrated($this);
    }

    public function markMigrated()
    {
        $this->_configuration->createMigrationTable();
        $this->_connection->executeQuery("INSERT INTO " . $this->_configuration->getMigrationsTableName() . " (version) VALUES (?)", array($this->_version));
    }

    public function markNotMigrated()
    {
        $this->_configuration->createMigrationTable();
        $this->_connection->executeQuery("DELETE FROM " . $this->_configuration->getMigrationsTableName() . " WHERE version = '$this->_version'");
    }

    /**
     * Add some SQL queries to this versions migration
     *
     * @param mixed $sql
     * @return void
     */
    public function addSql($sql)
    {
        if (is_array($sql)) {
            foreach ($sql as $query) {
                $this->_sql[] = $query;
            }
        } else {
            $this->_sql[] = $sql;
        }
    }

    /**
     * Write a migration SQL file to the given path
     *
     * @param string $path          The path to write the migration SQL file.
     * @param string $direction     The direction to execute.
     * @return bool $written
     */
    public function writeSqlFile($path, $direction = 'up')
    {
        $queries = $this->execute($direction, true);

        $string  = sprintf("# Doctrine Migration File Generated on %s\n", date('Y-m-d H:m:s'));

        $string .= "\n# Version " . $this->_version . "\n";
        foreach ($queries as $query) {
            $string .= $query . ";\n";
        }
        if (is_dir($path)) {
            $path = realpath($path);
            $path = $path . '/doctrine_migration_' . date('YmdHis') . '.sql';
        }

        $this->_outputWriter->write("\n".sprintf('Writing migration file to "<info>%s</info>"', $path));

        return file_put_contents($path, $string);
    }

    /**
     * Execute this migration version up or down and and return the SQL.
     *
     * @param string $direction   The direction to execute the migration.
     * @param string $dryRun      Whether to not actually execute the migration SQL and just do a dry run.
     * @return array $sql
     * @throws Exception when migration fails
     */
    public function execute($direction, $dryRun = false)
    {
        $this->_sql = array();

        $this->_connection->beginTransaction();

        try {
            $start = microtime(true);

            $this->_state = self::STATE_PRE;
            $fromSchema = $this->_sm->createSchema();
            $this->_migration->{'pre' . ucfirst($direction)}($fromSchema);

            if ($direction === 'up') {
                $this->_outputWriter->write("\n" . sprintf('  <info>++</info> migrating <comment>%s</comment>', $this->_version) . "\n");
            } else {
                $this->_outputWriter->write("\n" . sprintf('  <info>--</info> reverting <comment>%s</comment>', $this->_version) . "\n");
            }

            $this->_state = self::STATE_EXEC;

            $toSchema = clone $fromSchema;
            $this->_migration->$direction($toSchema);
            $this->addSql($fromSchema->getMigrateToSql($toSchema, $this->_platform));

            if ($dryRun === false) {
                if ($this->_sql) {
                    $count = count($this->_sql);
                    foreach ($this->_sql as $query) {
                        $this->_outputWriter->write('     <comment>-></comment> ' . $query);
                        $this->_connection->executeQuery($query);
                    }

                    if ($direction === 'up') {
                        $this->markMigrated();
                    } else {
                        $this->markNotMigrated();
                    }
                } else {
                    $this->_outputWriter->write(sprintf('<error>Migration %s was executed but did not result in any SQL statements.</error>', $this->_version));
                }
            } else {
                foreach ($this->_sql as $query) {
                    $this->_outputWriter->write('     <comment>-></comment> ' . $query);
                }
            }

            $this->_state = self::STATE_POST;
            $this->_migration->{'post' . ucfirst($direction)}($toSchema);

            $end = microtime(true);
            $this->_time = round($end - $start, 2);
            if ($direction === 'up') {
                $this->_outputWriter->write(sprintf("\n  <info>++</info> migrated (%ss)", $this->_time));
            } else {
                $this->_outputWriter->write(sprintf("\n  <info>--</info> reverted (%ss)", $this->_time));
            }

            $this->_connection->commit();

            return $this->_sql;
        } catch(SkipMigrationException $e) {
            $this->_connection->rollback();

            // now mark it as migrated
            if ($direction === 'up') {
                $this->markMigrated();
            } else {
                $this->markNotMigrated();
            }

            $this->_outputWriter->write(sprintf("\n  <info>SS</info> skipped (Reason: %s)",  $e->getMessage()));
        } catch (\Exception $e) {

            $this->_outputWriter->write(sprintf(
                '<error>Migration %s failed during %s. Error %s</error>',
                $this->_version, $this->getExecutionState(), $e->getMessage()
            ));

            $this->_connection->rollback();

            $this->_state = self::STATE_NONE;
            throw $e;
        }
        $this->_state = self::STATE_NONE;
    }

    public function getExecutionState()
    {
        switch($this->_state) {
            case self::STATE_PRE:
                return 'Pre-Checks';
            case self::STATE_POST:
                return 'Post-Checks';
            case self::STATE_EXEC:
                return 'Execution';
            default:
                return 'No State';
        }
    }

    /**
     * Returns the time this migration version took to execute
     *
     * @return integer $time The time this migration version took to execute
     */
    public function getTime()
    {
        return $this->_time;
    }

    public function __toString()
    {
        return $this->_version;
    }
}
