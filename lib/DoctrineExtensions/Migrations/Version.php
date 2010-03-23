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

namespace DoctrineExtensions\Migrations;

use DoctrineExtensions\Migrations\Configuration\Configuration,
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
    private $_configuration;
    private $_version;
    private $_class;
    private $_fromSchema;
    private $_toSchema;
    private $_sql = array();

    public function __construct(Configuration $configuration, $version, $class)
    {
        $this->_configuration = $configuration;
        $this->_version = $version;
        $this->_class = $class;
        $this->_connection = $configuration->getConnection();
        $this->_sm = $this->_connection->getSchemaManager();
        $this->_platform = $this->_connection->getDatabasePlatform();
        $this->_printer = $configuration->getPrinter();
        $this->_migration = new $class($this);
    }

    /**
     * Get the string version in the format YYYYMMDDHHMMSS
     *
     * @return string $version
     */
    public function getVersion()
    {
        return $this->_version;
    }

    /**
     * Get the Migrations Configuration object instance
     *
     * @return Configuration $configuration
     */
    public function getConfiguration()
    {
        return $this->_configuration;
    }

    /**
     * Check if this version has been migrated or not. If you pass a boolean value 
     * it will mark this version as migrated or if you pass false it will unmark
     * this version as migrated.
     *
     * @param bool $bool
     * @return mixed
     */
    public function isMigrated($bool = null)
    {
        if ($bool === null) {
            return $this->_configuration->hasVersionMigrated($this);
        } else {
            $this->_configuration->createMigrationTable();
            if ($bool === true) {
                $this->_printer->writeln('');
                $this->_printer->writeln('  ++ ' . $this->_printer->format('migrated', 'INFO'));
                $this->_connection->execute("INSERT INTO " . $this->_configuration->getMigrationTableName() . " (version) VALUES (?)", array($this->_version));
            } else {
                $this->_printer->writeln('');
                $this->_printer->writeln('  -- ' . $this->_printer->format('reverted', 'INFO'));
                $this->_connection->execute("DELETE FROM " . $this->_configuration->getMigrationTableName() . " WHERE version = '$this->_version'");        
            }
        }
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
            $path = $path . '/doctrine_migration_' . date('YmdHms') . '.sql';
        }

        $this->_printer->writeln('');
        $this->announce(sprintf('Writing migration file to "'.$this->_printer->format('%s', 'KEYWORD').'"', $path));

        return file_put_contents($path, $string);
    }

    /**
     * Execute this migration version up or down and and return the SQL.
     *
     * @param string $direction   The direction to execute the migration.
     * @param string $dryRun      Whether to not actually execute the migration SQL and just do a dry run.
     * @return array $sql
     */
    public function execute($direction, $dryRun = false)
    {
        $this->_sql = array();

        $this->_connection->beginTransaction();

        try {
            $this->_fromSchema = $this->_sm->createSchema();
            $this->_migration->{'pre' . ucfirst($direction)}($this->_fromSchema);

            $this->_printer->writeln('');
            if ($direction === 'up') {
                $this->_printer->writeln('  ++ ' . $this->_printer->format(sprintf('migrating %s', $this->_version), 'KEYWORD'));
            } else {
                $this->_printer->writeln('  -- ' . $this->_printer->format(sprintf('reverting %s', $this->_version), 'KEYWORD'));
            }
            $this->_printer->writeln('');

            $this->_toSchema = clone $this->_fromSchema;
            $this->_migration->$direction($this->_toSchema);
            $this->addSchemaChangesSql($this->_toSchema);

            if ($dryRun === false) {
                if ($this->_sql) {
                    $count = count($this->_sql);
                    foreach ($this->_sql as $query) {
                        $this->announceQuery($query);
                        $this->_connection->execute($query);
                    }
                    $this->isMigrated($direction === 'up' ? true : false);
                } else {
                    $this->announce('No SQL queries to execute.', 'ERROR');
                }
            } else {
                foreach ($this->_sql as $query) {
                    $this->announceQuery($query);
                }
            }

            $this->_connection->commit();

            $this->_migration->{'post' . ucfirst($direction)}($this->_toSchema);

            return $this->_sql;
        } catch (\Exception $e) {
            $this->_printer->writeln('');
            $this->announce('failed ... ' . $e->getMessage(), 'ERROR');

            $this->_printer->writeln('');
            $this->_printer->writeln($e->getTraceAsString());

            $this->_connection->rollback();
        }
    }

    /**
     * Migrate the passed schema and produce the needed SQL and add it to the migrations
     * array of SQL statements to executes
     *
     * @param Schema $toSchema 
     * @return void
     */
    public function addSchemaChangesSql(Schema $toSchema)
    {
        $sql = $this->_fromSchema->getMigrateToSql($toSchema, $this->_platform);
        $this->_fromSchema = clone $toSchema;
        $this->addSql($sql);
        return $sql;
    }

    public function announce($message, $format = 'NONE')
    {
        $this->_printer->writeln($message, $format);
    }

    public function announceQuery($query)
    {
        $this->_printer->writeln($this->_printer->format('     -> ', 'INFO') . $query);
    }

    public function __toString()
    {
        return $this->_version;
    }
}