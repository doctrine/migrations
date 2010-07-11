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

namespace Doctrine\DBAL\Migrations\Configuration;

use Doctrine\DBAL\Connection,
    Doctrine\DBAL\Migrations\MigrationException,
    Doctrine\DBAL\Migrations\Version,
    Doctrine\DBAL\Migrations\OutputWriter,
    Doctrine\DBAL\Schema\Table,
    Doctrine\DBAL\Schema\Column,
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
    /** Name of this set of migrations */
    private $_name;

    /** Flag for whether or not the migration table has been created */
    private $_migrationTableCreated = false;

    /** Connection instance to use for migrations */
    private $_connection;

    /** OutputWriter instance for writing output during migrations */
    private $_outputWriter;

    /** The migration table name to track versions in */
    private $_migrationsTableName = 'doctrine_migration_versions';

    /** The path to a directory where new migration classes will be written */
    private $_migrationsDirectory;

    /** Namespace the migration classes live in */
    private $_migrationsNamespace;

    /** Array of the registered migrations */
    private $_migrations = array();

    /**
     * Construct a migration configuration object.
     *
     * @param Connection $connection      A Connection instance
     * @param OutputWriter $outputWriter  A OutputWriter instance
     */
    public function __construct(Connection $connection, OutputWriter $outputWriter = null)
    {
        $this->_connection = $connection;
        if ($outputWriter === null) {
            $outputWriter = new OutputWriter();
        }
        $this->_outputWriter = $outputWriter;
    }

    /**
     * Validation that this instance has all the required properties configured
     *
     * @return void
     * @throws MigrationException
     */
    public function validate()
    {
        if ( ! $this->_migrationsNamespace) {
            throw MigrationException::migrationsNamespaceRequired();
        }
        if ( ! $this->_migrationsDirectory) {
            throw MigrationException::migrationsDirectoryRequired();
        }
    }

    /**
     * Set the name of this set of migrations
     *
     * @param string $name The name of this set of migrations
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * Returns the name of this set of migrations
     *
     * @return string $name The name of this set of migrations
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Returns the OutputWriter instance
     *
     * @return OutputWriter $outputWriter  The OutputWriter instance
     */
    public function getOutputWriter()
    {
        return $this->_outputWriter;
    }

    /**
     * Returns a timestamp version as a formatted date
     *
     * @param string $version 
     * @return string $formattedVersion The formatted version
     */
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

    /**
     * Returns the Connection instance
     *
     * @return Connection $connection  The Connection instance
     */
    public function getConnection()
    {
        return $this->_connection;
    }

    /**
     * Set the migration table name
     *
     * @param string $tableName The migration table name
     */
    public function setMigrationsTableName($tableName)
    {
        $this->_migrationsTableName = $tableName;
    }

    /**
     * Returns the migration table name
     *
     * @return string $migrationsTableName The migration table name
     */
    public function getMigrationsTableName()
    {
        return $this->_migrationsTableName;
    }

    /**
     * Set the new migrations directory where new migration classes are generated
     *
     * @param string $migrationsDirectory The new migrations directory 
     */
    public function setMigrationsDirectory($migrationsDirectory)
    {
        $this->_migrationsDirectory = $migrationsDirectory;
    }

    /**
     * Returns the new migrations directory where new migration classes are generated
     *
     * @return string $migrationsDirectory The new migrations directory
     */
    public function getMigrationsDirectory()
    {
        return $this->_migrationsDirectory;
    }

    /**
     * Set the migrations namespace
     *
     * @param string $migrationsNamespace The migrations namespace
     */
    public function setMigrationsNamespace($migrationsNamespace)
    {
        $this->_migrationsNamespace = $migrationsNamespace;
    }

    /**
     * Returns the migrations namespace
     *
     * @return string $migrationsNamespace The migrations namespace
     */
    public function getMigrationsNamespace()
    {
        return $this->_migrationsNamespace;
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
        $files = glob($path . '/Version*.php');
        $versions = array();
        foreach ($files as $file) {
            require_once($file);
            $info = pathinfo($file);
            $version = substr($info['filename'], 7);
            $class = $this->_migrationsNamespace . '\\' . $info['filename'];
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
     * Returns the Version instance for a given version in the format YYYYMMDDHHMMSS.
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

        $version = $this->_connection->fetchColumn("SELECT version FROM " . $this->_migrationsTableName . " WHERE version = '" . $version->getVersion() . "'");
        return $version !== false ? true : false;
    }

    /**
     * Returns the current migrated version from the versions table.
     *
     * @return bool $currentVersion
     */
    public function getCurrentVersion()
    {
        $this->createMigrationTable();

        $result = $this->_connection->fetchColumn("SELECT version FROM " . $this->_migrationsTableName . " ORDER BY version DESC LIMIT 1");
        return $result !== false ? (string) $result : '0';
    }

    /**
     * Returns the total number of executed migration versions
     *
     * @return integer $count
     */
    public function getNumberOfExecutedMigrations()
    {
        $this->createMigrationTable();

        $result = $this->_connection->fetchColumn("SELECT COUNT(version) FROM " . $this->_migrationsTableName);
        return $result !== false ? $result : 0;
    }

    /**
     * Returns the total number of available migration versions
     *
     * @return integer $count
     */
    public function getNumberOfAvailableMigrations()
    {
        return count($this->_migrations);
    }

    /**
     * Returns the latest available migration version.
     *
     * @return string $version  The version string in the format YYYYMMDDHHMMSS.
     */
    public function getLatestVersion()
    {
        $versions = array_keys($this->_migrations);
        $latest = end($versions);
        return $latest !== false ? (string) $latest : '0';
    }

    /**
     * Create the migration table to track migrations with.
     *
     * @return bool $created  Whether or not the table was created.
     */
    public function createMigrationTable()
    {
        $this->validate();

        if ($this->_migrationTableCreated) {
            return false;
        }

        $schema = $this->_connection->getSchemaManager()->createSchema();
        if ( ! $schema->hasTable($this->_migrationsTableName)) {
            $columns = array(
                'version' => new Column('version', Type::getType('string'), array('length' => 14)),
            );
            $table = new Table($this->_migrationsTableName, $columns);
            $table->setPrimaryKey(array('version'));
            $this->_connection->getSchemaManager()->createTable($table);

            $this->_migrationTableCreated = true;

            return true;
        }
        return false;
    }

    /**
     * Returns the array of migrations to executed based on the given direction
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