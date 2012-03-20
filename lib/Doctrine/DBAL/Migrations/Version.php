<?php
/*
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
    private $configuration;

    /**
     * The OutputWriter object instance used for outputting information
     *
     * @var OutputWriter
     */
    private $outputWriter;

    /**
     * The version in timestamp format (YYYYMMDDHHMMSS)
     *
     * @param int
     */
    private $version;

    /**
     * @var AbstractSchemaManager
     */
    private $sm;

    /**
     * @var AbstractPlatform
     */
    private $platform;

    /**
     * The migration instance for this version
     *
     * @var AbstractMigration
     */
    private $migration;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $class;

    /** The array of collected SQL statements for this version */
    private $sql = array();

    /** The array of collected parameters for SQL statements for this version */
    private $params = array();

    /** The array of collected types for SQL statements for this version */
    private $types = array();

    /** The time in seconds that this migration version took to execute */
    private $time;

    /**
     * @var int
     */
    private $state = self::STATE_NONE;

    public function __construct(Configuration $configuration, $version, $class)
    {
        $this->configuration = $configuration;
        $this->outputWriter = $configuration->getOutputWriter();
        $this->class = $class;
        $this->connection = $configuration->getConnection();
        $this->sm = $this->connection->getSchemaManager();
        $this->platform = $this->connection->getDatabasePlatform();
        $this->migration = new $class($this);
        $this->version = $this->migration->getName() ?: $version;
    }

    /**
     * Returns the string version in the format YYYYMMDDHHMMSS
     *
     * @return string $version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Returns the Migrations Configuration object instance
     *
     * @return Configuration $configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Check if this version has been migrated or not.
     *
     * @param bool $bool
     * @return mixed
     */
    public function isMigrated()
    {
        return $this->configuration->hasVersionMigrated($this);
    }

    public function markMigrated()
    {
        $this->configuration->createMigrationTable();
        $this->connection->executeQuery("INSERT INTO " . $this->configuration->getMigrationsTableName() . " (version) VALUES (?)", array($this->version));
    }

    public function markNotMigrated()
    {
        $this->configuration->createMigrationTable();
        $this->connection->executeQuery("DELETE FROM " . $this->configuration->getMigrationsTableName() . " WHERE version = ?", array($this->version));
    }

    /**
     * Add some SQL queries to this versions migration
     *
     * @param mixed $sql
     * @param array $params
     * @param array $types
     * @return void
     */
    public function addSql($sql, array $params = array(), array $types = array())
    {
        if (is_array($sql)) {
            foreach ($sql as $key => $query) {
                $this->sql[] = $query;
                if (isset($params[$key])) {
                    $this->params[count($this->sql) - 1] = $params[$key];
                    $this->types[count($this->sql) - 1] = isset($types[$key]) ? $types[$key] : array();
                }
            }
        } else {
            $this->sql[] = $sql;
            if ($params) {
                $this->params[count($this->sql) - 1] = $params;
                $this->types[count($this->sql) - 1] = $types ?: array();
            }
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

        $string .= "\n# Version " . $this->version . "\n";
        foreach ($queries as $query) {
            $string .= $query . ";\n";
        }
        if (is_dir($path)) {
            $path = realpath($path);
            $path = $path . '/doctrine_migration_' . date('YmdHis') . '.sql';
        }

        $this->outputWriter->write("\n".sprintf('Writing migration file to "<info>%s</info>"', $path));

        return file_put_contents($path, $string);
    }

    /**
     * @return AbstractMigration
     */
    public function getMigration()
    {
        return $this->migration;
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
        $this->sql = array();

        $this->connection->beginTransaction();

        try {
            $start = microtime(true);

            $this->state = self::STATE_PRE;
            $fromSchema = $this->sm->createSchema();
            $this->migration->{'pre' . ucfirst($direction)}($fromSchema);

            if ($direction === 'up') {
                $this->outputWriter->write("\n" . sprintf('  <info>++</info> migrating <comment>%s</comment>', $this->version) . "\n");
            } else {
                $this->outputWriter->write("\n" . sprintf('  <info>--</info> reverting <comment>%s</comment>', $this->version) . "\n");
            }

            $this->state = self::STATE_EXEC;

            $toSchema = clone $fromSchema;
            $this->migration->$direction($toSchema);
            $this->addSql($fromSchema->getMigrateToSql($toSchema, $this->platform));

            if ($dryRun === false) {
                if ($this->sql) {
                    foreach ($this->sql as $key => $query) {
                        if ( ! isset($this->params[$key])) {
                            $this->outputWriter->write('     <comment>-></comment> ' . $query);
                            $this->connection->executeQuery($query);
                        } else {
                            $this->outputWriter->write(sprintf('    <comment>-</comment> %s (with parameters)', $query));
                            $this->connection->executeQuery($query, $this->params[$key], $this->types[$key]);
                        }
                    }
                } else {
                    $this->outputWriter->write(sprintf('<error>Migration %s was executed but did not result in any SQL statements.</error>', $this->version));
                }

                if ($direction === 'up') {
                    $this->markMigrated();
                } else {
                    $this->markNotMigrated();
                }

            } else {
                foreach ($this->sql as $query) {
                    $this->outputWriter->write('     <comment>-></comment> ' . $query);
                }
            }

            $this->state = self::STATE_POST;
            $this->migration->{'post' . ucfirst($direction)}($toSchema);

            $end = microtime(true);
            $this->time = round($end - $start, 2);
            if ($direction === 'up') {
                $this->outputWriter->write(sprintf("\n  <info>++</info> migrated (%ss)", $this->time));
            } else {
                $this->outputWriter->write(sprintf("\n  <info>--</info> reverted (%ss)", $this->time));
            }

            $this->connection->commit();

            return $this->sql;
        } catch(SkipMigrationException $e) {
            $this->connection->rollback();

            if ($dryRun == false) {
                // now mark it as migrated
                if ($direction === 'up') {
                    $this->markMigrated();
                } else {
                    $this->markNotMigrated();
                }
            }

            $this->outputWriter->write(sprintf("\n  <info>SS</info> skipped (Reason: %s)",  $e->getMessage()));
        } catch (\Exception $e) {

            $this->outputWriter->write(sprintf(
                '<error>Migration %s failed during %s. Error %s</error>',
                $this->version, $this->getExecutionState(), $e->getMessage()
            ));

            $this->connection->rollback();

            $this->state = self::STATE_NONE;
            throw $e;
        }
        $this->state = self::STATE_NONE;
    }

    public function getExecutionState()
    {
        switch($this->state) {
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
        return $this->time;
    }

    public function __toString()
    {
        return $this->version;
    }
}
