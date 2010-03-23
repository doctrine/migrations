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

namespace DoctrineExtensions\Migrations\Configuration;

use Doctrine\Dbal\Connection,
    Doctrine\Common\Cli\Printers\AbstractPrinter,
    Doctrine\Common\Cli\Printers\AnsiColorPrinter,
    DoctrineExtensions\Migrations\MigrationException,
    DoctrineExtensions\Migrations\Version,
    Doctrine\Dbal\Schema\Table,
    Doctrine\Dbal\Schema\Column,
    Doctrine\DBAL\Types\Type;

/**
 * Default Migration Configurtion object used for configuring an instance of
 * the Migration class. Set the connection, version table name, register migration
 * classes/versions, etc.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Configuration
{
    private $_migrationTableCreated = false;
    private $_connection;
    private $_printer;
    private $_migrationTableName = 'doctrine_migration_versions';
    private $_migrations = array();

    /**
     * Base migration configuration object.
     *
     * @param Connection $connection      The connection instance we are migrating.
     * @param AbstractPrinter $printer    CLI Printer instance used for useful output about your migration.
     * @author Jonathan Wage
     */
    public function __construct(Connection $connection, AbstractPrinter $printer = null)
    {
        $this->_connection = $connection;
        $this->_printer = $printer ?: new AnsiColorPrinter;
    }

    public function formatVersion($version)
    {
        return sprintf('%s-%s-%s %s:%s:%s',
            substr($version, 0, 4),
            substr($version, 4, 2),
            substr($version, 6, 2),
            substr($version, 8, 2),
            substr($version, 10, 2),
            substr($version, 12, 2)
        );
    }

    public function getConnection()
    {
        return $this->_connection;
    }

    public function setPrinter(AbstractPrinter $printer)
    {
        $this->_printer = $printer;
    }

    public function getPrinter()
    {
        return $this->_printer;
    }

    public function setMigrationTableName($tableName)
    {
        $this->_migrationTableName = $tableName;
    }

    public function getMigrationTableName()
    {
        return $this->_migrationTableName;
    }

    /**
     * Register migrations from a given directory. Recursively finds all files
     * with the pattern VersionYYYYMMDDHHMMSS.php as the filename and registers
     * them as migrations.
     *
     * @param string $path  The root directory to where some migration classes live.
     * @return $migrations  The array of migrations registered.
     */
    public function registerMigrationsFromDirectory($path)
    {
        $path = realpath($path);
        $path = rtrim($path, '/');
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        $versions = array();
        foreach ($iterator as $file) {
            $version = substr($file->getBasename('.php'), 7);
            $class = str_replace('/', '\\', substr($file->getPathname(), strlen($path . '/'), -4));
            $versions[] = $this->registerMigration($version, $class);
        }
        return $versions;
    }

    /**
     * Register a single migration version to be executed by a AbstractMigration
     * class.
     *
     * @param string $version  The version of the migration in the format YYYYMMDDHHMMSS.
     * @param string $class    The migration class to execute for the version.
     */
    public function registerMigration($version, $class)
    {
        $version = (string) $version;
        $class = (string) $class;
        if (isset($this->_migrations[$version])) {
            throw MigrationException::duplicateMigrationVersion($version, get_class($this->_migrations[$version]));
        }
        $version = new Version($this, $version, $class);
        $this->_migrations[$version->getVersion()] = $version;
        ksort($this->_migrations);
        return $version;
    }

    /**
     * Register an array of migrations. Each key of the array is the version and
     * the value is the migration class name.
     *
     *
     * @param array $migrations
     * @return void
     * @author Jonathan Wage
     */
    public function registerMigrations(array $migrations)
    {
        $versions = array();
        foreach ($migrations as $version => $class) {
            $versions[] = $this->registerMigration($version, $class);
        }
        return $versions;
    }

    /**
     * Get the array of registered migration versions.
     *
     * @return array $migrations
     */
    public function getMigrations()
    {
        return $this->_migrations;
    }

    /**
     * Get the Version instance for a given version in the format YYYYMMDDHHMMSS.
     *
     * @param string $version   The version string in the format YYYYMMDDHHMMSS.
     * @return Version $version
     * @throws MigrationException $exception Throws exception if migration version does not exist.
     */
    public function getVersion($version)
    {
        if ( ! isset($this->_migrations[$version])) {
            MigrationException::unknownMigrationVersion($version);
        }
        return $this->_migrations[$version];
    }

    /**
     * Check if a version exists.
     *
     * @param string $version
     * @return bool $exists
     */
    public function hasVersion($version)
    {
        return isset($this->_migrations[$version]) ? true : false;
    }

    /**
     * Check if a version has been migrated or not yet
     *
     * @param Version $version
     * @return bool $migrated
     */
    public function hasVersionMigrated(Version $version)
    {
        $this->createMigrationTable();

        $version = $this->_connection->fetchColumn("SELECT version FROM " . $this->_migrationTableName . " WHERE version = '" . $version->getVersion() . "'");
        return $version !== false ? true : false;
    }

    /**
     * Get the current migrated version from the versions table.
     *
     * @return bool $currentVersion
     */
    public function getCurrentVersion()
    {
        $this->createMigrationTable();

        $result = $this->_connection->fetchColumn("SELECT version FROM " . $this->_migrationTableName . " ORDER BY version DESC LIMIT 1");
        return $result !== false ? $result : 0;
    }

    /**
     * Get the total number of executed migration versions
     *
     * @return integer $count
     */
    public function getNumberOfExecutedMigrations()
    {
        $this->createMigrationTable();

        $result = $this->_connection->fetchColumn("SELECT COUNT(version) FROM " . $this->_migrationTableName);
        return $result !== false ? $result : 0;
    }

    /**
     * Get the total number of available migration versions
     *
     * @return integer $count
     */
    public function getNumberOfAvailableMigrations()
    {
        return count($this->_migrations);
    }

    /**
     * Get the latest available migration version.
     *
     * @return string $version  The version string in the format YYYYMMDDHHMMSS.
     */
    public function getLatestVersion()
    {
        $versions = array_keys($this->_migrations);
        $latest = end($versions);
        return $latest !== false ? $latest : 0;
    }

    /**
     * Create the migration table to track migrations with.
     *
     * @return bool $created  Whether or not the table was created.
     */
    public function createMigrationTable()
    {
        if ($this->_migrationTableCreated) {
            return false;
        }

        $schema = $this->_connection->getSchemaManager()->createSchema();
        if ( ! $schema->hasTable($this->_migrationTableName)) {
            $columns = array(
                'version' => new Column('version', Type::getType('string'), array('length' => 14)),
            );
            $table = new Table($this->_migrationTableName, $columns);
            $table->setPrimaryKey(array('version'));
            $this->_connection->getSchemaManager()->createTable($table);

            $this->_migrationTableCreated = true;

            return true;
        }
        return false;
    }

    /**
     * Get the array of migrations to executed based on the given direction
     * and target version number.
     *
     * @param string $direction    The direction we are migrating.
     * @param string $to           The version to migrate to.
     * @return array $migrations   The array of migrations we can execute.
     */
    public function getMigrationsToExecute($direction, $to)
    {
        if ($direction === 'down') {
            $allVersions = array_reverse(array_keys($this->_migrations));
            $classes = array_reverse(array_values($this->_migrations));
            $allVersions = array_combine($allVersions, $classes);
        } else {
            $allVersions = $this->_migrations;
        }
        $versions = array();
        foreach ($allVersions as $version) {
            if ($this->_shouldExecuteMigration($direction, $version, $to)) {
                $versions[$version->getVersion()] = $version;
            }
        }
        return $versions;
    }

    /**
     * Check if we should execute a migration for a given direction and target
     * migration version.
     *
     * @param string $direction   The direction we are migrating.
     * @param Version $version    The Version instance to check.
     * @param string $to          The version we are migrating to.
     * @return void
     * @author Jonathan Wage
     */
    private function _shouldExecuteMigration($direction, Version $version, $to)
    {
        if ($direction === 'down') {
            if ( ! $this->hasVersionMigrated($version)) {
                return false;
            }
            return $version->getVersion() > $to ? true : false;
        } else if ($direction === 'up') {
            if ($this->hasVersionMigrated($version)) {
                return false;
            }
            return $version->getVersion() <= $to ? true : false;
        }
    }
}