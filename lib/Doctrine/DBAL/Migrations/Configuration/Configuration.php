<?php

namespace Doctrine\DBAL\Migrations\Configuration;

use Doctrine\Common\EventArgs;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connections\MasterSlaveConnection;
use Doctrine\DBAL\Migrations\FileQueryWriter;
use Doctrine\DBAL\Migrations\Finder\MigrationDeepFinderInterface;
use Doctrine\DBAL\Migrations\MigrationException;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\QueryWriter;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Migrations\Finder\MigrationFinderInterface;
use Doctrine\DBAL\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

/**
 * Default Migration Configuration object used for configuring an instance of
 * the Migration class. Set the connection, version table name, register migration
 * classes/versions, etc.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Configuration
{
    /**
     * Configure versions to be organized by year.
     */
    const VERSIONS_ORGANIZATION_BY_YEAR = 'year';

    /**
     * Configure versions to be organized by year and month.
     *
     * @var string
     */
    const VERSIONS_ORGANIZATION_BY_YEAR_AND_MONTH = 'year_and_month';

    /**
     * The date format for new version numbers
     */
    const VERSION_FORMAT = 'YmdHis';

    /**
     * Name of this set of migrations
     *
     * @var string
     */
    private $name;

    /**
     * Flag for whether or not the migration table has been created
     *
     * @var boolean
     */
    private $migrationTableCreated = false;

    /**
     * Connection instance to use for migrations
     *
     * @var Connection
     */
    private $connection;

    /**
     * OutputWriter instance for writing output during migrations
     *
     * @var OutputWriter
     */
    private $outputWriter;

    /**
     * The migration finder implementation -- used to load migrations from a
     * directory.
     *
     * @var MigrationFinderInterface
     */
    private $migrationFinder;

    /**
     * @var QueryWriter
     */
    private $queryWriter;

    /**
     * The migration table name to track versions in
     *
     * @var string
     */
    private $migrationsTableName = 'doctrine_migration_versions';

    /**
     * The migration column name to track versions in
     *
     * @var string
     */
    private $migrationsColumnName = 'version';

    /**
     * The path to a directory where new migration classes will be written
     *
     * @var string
     */
    private $migrationsDirectory;

    /**
     * Namespace the migration classes live in
     *
     * @var string
     */
    private $migrationsNamespace;

    /**
     * Array of the registered migrations
     *
     * @var Version[]
     */
    private $migrations = [];

    /**
     * Versions are organized by year.
     *
     * @var boolean
     */
    private $migrationsAreOrganizedByYear = false;

    /**
     * Versions are organized by year and month.
     *
     * @var boolean
     */
    private $migrationsAreOrganizedByYearAndMonth = false;

    /**
     * The custom template path to be used in generate command
     *
     * @var string
     */
    private $customTemplate;

    /**
     * Prevent write queries.
     *
     * @var bool
     */
    private $isDryRun = false;

    /**
     * Construct a migration configuration object.
     *
     * @param Connection               $connection   A Connection instance
     * @param OutputWriter             $outputWriter A OutputWriter instance
     * @param MigrationFinderInterface $finder       Migration files finder
     * @param QueryWriter|null         $queryWriter
     */
    public function __construct(
        Connection $connection,
        OutputWriter $outputWriter = null,
        MigrationFinderInterface $finder = null,
        QueryWriter $queryWriter = null
    ) {
        $this->connection      = $connection;
        $this->outputWriter    = $outputWriter ?? new OutputWriter();
        $this->migrationFinder = $finder ?? new RecursiveRegexFinder();
        $this->queryWriter     = $queryWriter;
    }

    /**
     * @return bool
     */
    public function areMigrationsOrganizedByYear()
    {
        return $this->migrationsAreOrganizedByYear;
    }

    /**
     * @return bool
     */
    public function areMigrationsOrganizedByYearAndMonth()
    {
        return $this->migrationsAreOrganizedByYearAndMonth;
    }

    /**
     * Validation that this instance has all the required properties configured
     *
     * @throws MigrationException
     */
    public function validate()
    {
        if ( ! $this->migrationsNamespace) {
            throw MigrationException::migrationsNamespaceRequired();
        }
        if ( ! $this->migrationsDirectory) {
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
        $this->name = $name;
    }

    /**
     * Returns the name of this set of migrations
     *
     * @return string $name The name of this set of migrations
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the output writer.
     *
     * @param OutputWriter $outputWriter
     */
    public function setOutputWriter(OutputWriter $outputWriter)
    {
        $this->outputWriter = $outputWriter;
    }

    /**
     * Returns the OutputWriter instance
     *
     * @return OutputWriter $outputWriter  The OutputWriter instance
     */
    public function getOutputWriter()
    {
        return $this->outputWriter;
    }

    /**
     * Returns a timestamp version as a formatted date
     *
     * @param string $version
     *
     * @return string The formatted version
     * @deprecated
     */
    public function formatVersion($version)
    {
        return $this->getDateTime($version);
    }

    /**
     * Returns the datetime of a migration
     *
     * @param string $version
     * @return string
     */
    public function getDateTime($version)
    {
        $datetime = str_replace('Version', '', $version);
        $datetime = \DateTime::createFromFormat('YmdHis', $datetime);

        if ($datetime === false) {
            return '';
        }

        return $datetime->format('Y-m-d H:i:s');
    }

    /**
     * Returns the Connection instance
     *
     * @return Connection $connection  The Connection instance
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Set the migration table name
     *
     * @param string $tableName The migration table name
     */
    public function setMigrationsTableName($tableName)
    {
        $this->migrationsTableName = $tableName;
    }

    /**
     * Returns the migration table name
     *
     * @return string $migrationsTableName The migration table name
     */
    public function getMigrationsTableName()
    {
        return $this->migrationsTableName;
    }

    /**
     * Set the migration column name
     *
     * @param string $columnName The migration column name
     */
    public function setMigrationsColumnName($columnName)
    {
        $this->migrationsColumnName = $columnName;
    }

    /**
     * Returns the migration column name
     *
     * @return string $migrationsColumnName The migration column name
     */
    public function getMigrationsColumnName()
    {
        return $this->migrationsColumnName;
    }

    /**
     * Returns the quoted migration column name
     *
     * @return string The quouted migration column name
     */
    public function getQuotedMigrationsColumnName()
    {
        return $this->getMigrationsColumn()->getQuotedName($this->connection->getDatabasePlatform());
    }

    /**
     * Set the new migrations directory where new migration classes are generated
     *
     * @param string $migrationsDirectory The new migrations directory
     */
    public function setMigrationsDirectory($migrationsDirectory)
    {
        $this->migrationsDirectory = $migrationsDirectory;
    }

    /**
     * Returns the new migrations directory where new migration classes are generated
     *
     * @return string $migrationsDirectory The new migrations directory
     */
    public function getMigrationsDirectory()
    {
        return $this->migrationsDirectory;
    }

    /**
     * Set the migrations namespace
     *
     * @param string $migrationsNamespace The migrations namespace
     */
    public function setMigrationsNamespace($migrationsNamespace)
    {
        $this->migrationsNamespace = $migrationsNamespace;
    }

    /**
     * Returns the migrations namespace
     *
     * @return string $migrationsNamespace The migrations namespace
     */
    public function getMigrationsNamespace()
    {
        return $this->migrationsNamespace;
    }

    /**
     * Returns the custom template path
     *
     * @return string $customTemplate The custom template path
     */
    public function getCustomTemplate() : ?string
    {
        return $this->customTemplate;
    }

    /**
     * Set the custom template path
     *
     * @param string $customTemplate The custom template path
     */
    public function setCustomTemplate(?string $customTemplate) : void
    {
        $this->customTemplate = $customTemplate;
    }

    /**
     * Set the implementation of the migration finder.
     *
     * @param MigrationFinderInterface $finder The new migration finder
     * @throws MigrationException
     */
    public function setMigrationsFinder(MigrationFinderInterface $finder)
    {
        if (($this->migrationsAreOrganizedByYear || $this->migrationsAreOrganizedByYearAndMonth)
            && ! ($finder instanceof MigrationDeepFinderInterface)) {
            throw MigrationException::configurationIncompatibleWithFinder(
                'organize-migrations',
                $finder
            );
        }

        $this->migrationFinder = $finder;
    }

    /**
     * Register migrations from a given directory. Recursively finds all files
     * with the pattern VersionYYYYMMDDHHMMSS.php as the filename and registers
     * them as migrations.
     *
     * @param string $path The root directory to where some migration classes live.
     *
     * @return Version[] The array of migrations registered.
     */
    public function registerMigrationsFromDirectory($path)
    {
        $this->validate();

        return $this->registerMigrations($this->findMigrations($path));
    }

    /**
     * Register a single migration version to be executed by a AbstractMigration
     * class.
     *
     * @param string $version The version of the migration in the format YYYYMMDDHHMMSS.
     * @param string $class   The migration class to execute for the version.
     *
     * @return Version
     *
     * @throws MigrationException
     */
    public function registerMigration($version, $class)
    {
        $this->ensureMigrationClassExists($class);

        $version = (string) $version;
        $class   = (string) $class;
        if (isset($this->migrations[$version])) {
            throw MigrationException::duplicateMigrationVersion($version, get_class($this->migrations[$version]));
        }
        $version                                  = new Version($this, $version, $class);
        $this->migrations[$version->getVersion()] = $version;
        ksort($this->migrations, SORT_STRING);

        return $version;
    }

    /**
     * Register an array of migrations. Each key of the array is the version and
     * the value is the migration class name.
     *
     *
     * @param array $migrations
     *
     * @return Version[]
     */
    public function registerMigrations(array $migrations)
    {
        $versions = [];
        foreach ($migrations as $version => $class) {
            $versions[] = $this->registerMigration($version, $class);
        }

        return $versions;
    }

    /**
     * Get the array of registered migration versions.
     *
     * @return Version[] $migrations
     */
    public function getMigrations()
    {
        return $this->migrations;
    }

    /**
     * Returns the Version instance for a given version in the format YYYYMMDDHHMMSS.
     *
     * @param string $version The version string in the format YYYYMMDDHHMMSS.
     *
     * @return Version
     *
     * @throws MigrationException Throws exception if migration version does not exist.
     */
    public function getVersion($version)
    {
        if (empty($this->migrations)) {
            $this->registerMigrationsFromDirectory($this->getMigrationsDirectory());
        }

        if ( ! isset($this->migrations[$version])) {
            throw MigrationException::unknownMigrationVersion($version);
        }

        return $this->migrations[$version];
    }

    /**
     * Check if a version exists.
     *
     * @param string $version
     *
     * @return boolean
     */
    public function hasVersion($version)
    {
        if (empty($this->migrations)) {
            $this->registerMigrationsFromDirectory($this->getMigrationsDirectory());
        }

        return isset($this->migrations[$version]);
    }

    /**
     * Check if a version has been migrated or not yet
     *
     * @param Version $version
     *
     * @return boolean
     */
    public function hasVersionMigrated(Version $version)
    {
        $this->connect();
        $this->createMigrationTable();

        $version = $this->connection->fetchColumn(
            "SELECT " . $this->getQuotedMigrationsColumnName() . " FROM " . $this->migrationsTableName . " WHERE " . $this->getQuotedMigrationsColumnName() . " = ?",
            [$version->getVersion()]
        );

        return $version !== false;
    }

    /**
     * Returns all migrated versions from the versions table, in an array.
     *
     * @return Version[]
     */
    public function getMigratedVersions()
    {
        $this->createMigrationTable();

        if ( ! $this->migrationTableCreated && $this->isDryRun) {
            return [];
        }

        $this->connect();

        $ret = $this->connection->fetchAll("SELECT " . $this->getQuotedMigrationsColumnName() . " FROM " . $this->migrationsTableName);

        return array_map('current', $ret);
    }

    /**
     * Returns an array of available migration version numbers.
     *
     * @return array
     */
    public function getAvailableVersions()
    {
        $availableVersions = [];

        if (empty($this->migrations)) {
            $this->registerMigrationsFromDirectory($this->getMigrationsDirectory());
        }

        foreach ($this->migrations as $migration) {
            $availableVersions[] = $migration->getVersion();
        }

        return $availableVersions;
    }

    /**
     * Returns the current migrated version from the versions table.
     *
     * @return string
     */
    public function getCurrentVersion()
    {
        $this->createMigrationTable();

        if ( ! $this->migrationTableCreated && $this->isDryRun) {
            return '0';
        }

        $this->connect();

        if (empty($this->migrations)) {
            $this->registerMigrationsFromDirectory($this->getMigrationsDirectory());
        }

        $where = null;
        if ( ! empty($this->migrations)) {
            $migratedVersions = [];
            foreach ($this->migrations as $migration) {
                $migratedVersions[] = sprintf("'%s'", $migration->getVersion());
            }
            $where = " WHERE " . $this->getQuotedMigrationsColumnName() . " IN (" . implode(', ', $migratedVersions) . ")";
        }

        $sql = sprintf(
            "SELECT %s FROM %s%s ORDER BY %s DESC",
            $this->getQuotedMigrationsColumnName(),
            $this->migrationsTableName,
            $where,
            $this->getQuotedMigrationsColumnName()
        );

        $sql    = $this->connection->getDatabasePlatform()->modifyLimitQuery($sql, 1);
        $result = $this->connection->fetchColumn($sql);

        return $result !== false ? (string) $result : '0';
    }

    /**
     * Returns the version prior to the current version.
     *
     * @return string|null A version string, or null if the current version is
     *                     the first.
     */
    public function getPrevVersion()
    {
        return $this->getRelativeVersion($this->getCurrentVersion(), -1);
    }

    /**
     * Returns the version following the current version.
     *
     * @return string|null A version string, or null if the current version is
     *                     the latest.
     */
    public function getNextVersion()
    {
        return $this->getRelativeVersion($this->getCurrentVersion(), 1);
    }

    /**
     * Returns the version with the specified offset to the specified version.
     *
     * @param string $version
     * @param string $delta
     * @return null|string A version string, or null if the specified version
     *                     is unknown or the specified delta is not within the
     *                     list of available versions.
     */
    public function getRelativeVersion($version, $delta)
    {
        if (empty($this->migrations)) {
            $this->registerMigrationsFromDirectory($this->getMigrationsDirectory());
        }

        $versions = array_map('strval', array_keys($this->migrations));
        array_unshift($versions, '0');
        $offset = array_search((string) $version, $versions, true);
        if ($offset === false || ! isset($versions[$offset + $delta])) {
            // Unknown version or delta out of bounds.
            return null;
        }

        return $versions[$offset + $delta];
    }

    /**
     * Returns the version with the specified to the current version.
     *
     * @param string $delta
     * @return null|string A version string, or null if the specified delta is
     *                     not within the list of available versions.
     */
    public function getDeltaVersion($delta)
    {
        $symbol = substr($delta, 0, 1);
        $number = (int) substr($delta, 1);

        if ($number <= 0) {
            return null;
        }

        if ($symbol == "+" || $symbol == "-") {
            return $this->getRelativeVersion($this->getCurrentVersion(), (int) $delta);
        }

        return null;
    }

    /**
     * Returns the version number from an alias.
     *
     * Supported aliases are:
     * - first: The very first version before any migrations have been run.
     * - current: The current version.
     * - prev: The version prior to the current version.
     * - next: The version following the current version.
     * - latest: The latest available version.
     *
     * If an existing version number is specified, it is returned verbatimly.
     *
     * @param string $alias
     * @return null|string A version number, or null if the specified alias
     *                     does not map to an existing version, e.g. if "next"
     *                     is passed but the current version is already the
     *                     latest.
     */
    public function resolveVersionAlias($alias)
    {
        if ($this->hasVersion($alias)) {
            return $alias;
        }
        switch ($alias) {
            case 'first':
                return '0';
            case 'current':
                return $this->getCurrentVersion();
            case 'prev':
                return $this->getPrevVersion();
            case 'next':
                return $this->getNextVersion();
            case 'latest':
                return $this->getLatestVersion();
            default:
                if (substr($alias, 0, 7) == 'current') {
                    return $this->getDeltaVersion(substr($alias, 7));
                }
                return null;
        }
    }

    /**
     * Returns the total number of executed migration versions
     *
     * @return integer
     */
    public function getNumberOfExecutedMigrations()
    {
        $this->connect();
        $this->createMigrationTable();

        $result = $this->connection->fetchColumn("SELECT COUNT(" . $this->getQuotedMigrationsColumnName() . ") FROM " . $this->migrationsTableName);

        return $result !== false ? $result : 0;
    }

    /**
     * Returns the total number of available migration versions
     *
     * @return integer
     */
    public function getNumberOfAvailableMigrations()
    {
        if (empty($this->migrations)) {
            $this->registerMigrationsFromDirectory($this->getMigrationsDirectory());
        }

        return count($this->migrations);
    }

    /**
     * Returns the latest available migration version.
     *
     * @return string The version string in the format YYYYMMDDHHMMSS.
     */
    public function getLatestVersion()
    {
        if (empty($this->migrations)) {
            $this->registerMigrationsFromDirectory($this->getMigrationsDirectory());
        }

        $versions = array_keys($this->migrations);
        $latest   = end($versions);

        return $latest !== false ? (string) $latest : '0';
    }

    /**
     * Create the migration table to track migrations with.
     *
     * @return boolean Whether or not the table was created.
     */
    public function createMigrationTable()
    {
        $this->validate();

        if ($this->migrationTableCreated) {
            return false;
        }

        $this->connect();
        if ($this->connection->getSchemaManager()->tablesExist([$this->migrationsTableName])) {
            $this->migrationTableCreated = true;

            return false;
        }

        if ($this->isDryRun) {
            return false;
        }

        $columns = [
            $this->migrationsColumnName => $this->getMigrationsColumn(),
        ];
        $table   = new Table($this->migrationsTableName, $columns);
        $table->setPrimaryKey([$this->migrationsColumnName]);
        $this->connection->getSchemaManager()->createTable($table);

        $this->migrationTableCreated = true;

        return true;
    }

    /**
     * Returns the array of migrations to executed based on the given direction
     * and target version number.
     *
     * @param string $direction The direction we are migrating.
     * @param string $to        The version to migrate to.
     *
     * @return Version[] $migrations   The array of migrations we can execute.
     */
    public function getMigrationsToExecute($direction, $to)
    {
        if (empty($this->migrations)) {
            $this->registerMigrationsFromDirectory($this->getMigrationsDirectory());
        }

        if ($direction === Version::DIRECTION_DOWN) {
            if (count($this->migrations)) {
                $allVersions = array_reverse(array_keys($this->migrations));
                $classes     = array_reverse(array_values($this->migrations));
                $allVersions = array_combine($allVersions, $classes);
            } else {
                $allVersions = [];
            }
        } else {
            $allVersions = $this->migrations;
        }
        $versions = [];
        $migrated = $this->getMigratedVersions();
        foreach ($allVersions as $version) {
            if ($this->shouldExecuteMigration($direction, $version, $to, $migrated)) {
                $versions[$version->getVersion()] = $version;
            }
        }

        return $versions;
    }

    /**
     * Use the connection's event manager to emit an event.
     *
     * @param string $eventName The event to emit.
     * @param EventArgs $args The event args instance to emit.
     */
    public function dispatchEvent($eventName, EventArgs $args = null)
    {
        $this->connection->getEventManager()->dispatchEvent($eventName, $args);
    }

    /**
     * Find all the migrations in a given directory.
     *
     * @param   string $path the directory to search.
     * @return  array
     */
    protected function findMigrations($path)
    {
        return $this->migrationFinder->findMigrations($path, $this->getMigrationsNamespace());
    }

    /**
     * @param bool $migrationsAreOrganizedByYear
     * @throws MigrationException
     */
    public function setMigrationsAreOrganizedByYear($migrationsAreOrganizedByYear = true)
    {
        $this->ensureOrganizeMigrationsIsCompatibleWithFinder();

        $this->migrationsAreOrganizedByYear = $migrationsAreOrganizedByYear;
    }

    /**
     * @param bool $migrationsAreOrganizedByYearAndMonth
     * @throws MigrationException
     */
    public function setMigrationsAreOrganizedByYearAndMonth($migrationsAreOrganizedByYearAndMonth = true)
    {
        $this->ensureOrganizeMigrationsIsCompatibleWithFinder();

        $this->migrationsAreOrganizedByYear         = $migrationsAreOrganizedByYearAndMonth;
        $this->migrationsAreOrganizedByYearAndMonth = $migrationsAreOrganizedByYearAndMonth;
    }

    /**
     * Generate a new migration version. A version is (usually) a datetime string.
     *
     * @param \DateTimeInterface|null $now Defaults to the current time in UTC
     * @return string The newly generated version
     */
    public function generateVersionNumber(\DateTimeInterface $now = null)
    {
        $now = $now ?: new \DateTime('now', new \DateTimeZone('UTC'));

        return $now->format(self::VERSION_FORMAT);
    }

    /**
     * Explicitely opens the database connection. This is done to play nice
     * with DBAL's MasterSlaveConnection. Which, in some cases, connects to a
     * follower when fetching the executed migrations. If a follower is lagging
     * significantly behind that means the migrations system may see unexecuted
     * migrations that were actually executed earlier.
     *
     * @return bool The same value returned from the `connect` method
     */
    protected function connect()
    {
        if ($this->connection instanceof MasterSlaveConnection) {
            return $this->connection->connect('master');
        }

        return $this->connection->connect();
    }

    /**
     * @throws MigrationException
     */
    private function ensureOrganizeMigrationsIsCompatibleWithFinder()
    {
        if ( ! ($this->migrationFinder instanceof MigrationDeepFinderInterface)) {
            throw MigrationException::configurationIncompatibleWithFinder(
                'organize-migrations',
                $this->migrationFinder
            );
        }
    }

    /**
     * Check if we should execute a migration for a given direction and target
     * migration version.
     *
     * @param string  $direction The direction we are migrating.
     * @param Version $version   The Version instance to check.
     * @param string  $to        The version we are migrating to.
     * @param array   $migrated  Migrated versions array.
     *
     * @return boolean
     */
    private function shouldExecuteMigration($direction, Version $version, $to, $migrated)
    {
        if ($direction === Version::DIRECTION_DOWN) {
            if ( ! in_array($version->getVersion(), $migrated, true)) {
                return false;
            }

            return $version->getVersion() > $to;
        }

        if ($direction === Version::DIRECTION_UP) {
            if (in_array($version->getVersion(), $migrated, true)) {
                return false;
            }

            return $version->getVersion() <= $to;
        }
    }

    /**
     * @param string $class
     * @throws MigrationException
     */
    private function ensureMigrationClassExists($class)
    {
        if ( ! class_exists($class)) {
            throw MigrationException::migrationClassNotFound($class, $this->getMigrationsNamespace());
        }
    }

    public function getQueryWriter() : QueryWriter
    {
        if ($this->queryWriter === null) {
            $this->queryWriter = new FileQueryWriter(
                $this->getQuotedMigrationsColumnName(),
                $this->migrationsTableName,
                $this->outputWriter
            );
        }

        return $this->queryWriter;
    }

    /**
     * @param bool $isDryRun
     */
    public function setIsDryRun($isDryRun)
    {
        $this->isDryRun = $isDryRun;
    }

    private function getMigrationsColumn() : Column
    {
        return new Column($this->migrationsColumnName, Type::getType('string'), ['length' => 255]);
    }
}
