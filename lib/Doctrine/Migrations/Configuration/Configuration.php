<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\Common\EventArgs;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connections\MasterSlaveConnection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\FileQueryWriter;
use Doctrine\Migrations\Finder\MigrationDeepFinder;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\Migrations\MigrationException;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\QueryWriter;
use Doctrine\Migrations\Version;
use const SORT_STRING;
use function array_combine;
use function array_keys;
use function array_map;
use function array_reverse;
use function array_search;
use function array_unshift;
use function array_values;
use function class_exists;
use function count;
use function end;
use function get_class;
use function implode;
use function in_array;
use function ksort;
use function sprintf;
use function str_replace;
use function substr;

class Configuration
{
    public const VERSIONS_ORGANIZATION_BY_YEAR = 'year';

    public const VERSIONS_ORGANIZATION_BY_YEAR_AND_MONTH = 'year_and_month';

    public const VERSION_FORMAT = 'YmdHis';

    /** @var string|null */
    private $name;

    /** @var bool */
    private $migrationTableCreated = false;

    /** @var Connection */
    private $connection;

    /** @var OutputWriter */
    private $outputWriter;

    /** @var MigrationFinder */
    private $migrationFinder;

    /** @var QueryWriter|null */
    private $queryWriter;

    /** @var string */
    private $migrationsTableName = 'doctrine_migration_versions';

    /** @var string */
    private $migrationsColumnName = 'version';

    /** @var string|null */
    private $migrationsDirectory;

    /** @var string|null */
    private $migrationsNamespace;

    /** @var Version[] */
    private $migrations = [];

    /** @var bool */
    private $migrationsAreOrganizedByYear = false;

    /** @var bool */
    private $migrationsAreOrganizedByYearAndMonth = false;

    /** @var string|null */
    private $customTemplate;

    /** @var bool */
    private $isDryRun = false;

    public function __construct(
        Connection $connection,
        ?OutputWriter $outputWriter = null,
        ?MigrationFinder $finder = null,
        ?QueryWriter $queryWriter = null
    ) {
        $this->connection      = $connection;
        $this->outputWriter    = $outputWriter ?? new OutputWriter();
        $this->migrationFinder = $finder ?? new RecursiveRegexFinder();
        $this->queryWriter     = $queryWriter;
    }

    public function areMigrationsOrganizedByYear() : bool
    {
        return $this->migrationsAreOrganizedByYear;
    }

    public function areMigrationsOrganizedByYearAndMonth() : bool
    {
        return $this->migrationsAreOrganizedByYearAndMonth;
    }

    /** @throws MigrationException */
    public function validate() : void
    {
        if ($this->migrationsNamespace === null) {
            throw MigrationException::migrationsNamespaceRequired();
        }

        if ($this->migrationsDirectory === null) {
            throw MigrationException::migrationsDirectoryRequired();
        }
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function getName() : ?string
    {
        return $this->name;
    }

    public function setOutputWriter(OutputWriter $outputWriter) : void
    {
        $this->outputWriter = $outputWriter;
    }

    public function getOutputWriter() : OutputWriter
    {
        return $this->outputWriter;
    }

    public function getDateTime(string $version) : string
    {
        $datetime = str_replace('Version', '', $version);
        $datetime = DateTime::createFromFormat('YmdHis', $datetime);

        if ($datetime === false) {
            return '';
        }

        return $datetime->format('Y-m-d H:i:s');
    }

    public function getConnection() : Connection
    {
        return $this->connection;
    }

    public function setMigrationsTableName(string $tableName) : void
    {
        $this->migrationsTableName = $tableName;
    }

    public function getMigrationsTableName() : string
    {
        return $this->migrationsTableName;
    }

    public function setMigrationsColumnName(string $columnName) : void
    {
        $this->migrationsColumnName = $columnName;
    }

    public function getMigrationsColumnName() : string
    {
        return $this->migrationsColumnName;
    }

    public function getQuotedMigrationsColumnName() : string
    {
        return $this->getMigrationsColumn()->getQuotedName($this->connection->getDatabasePlatform());
    }

    public function setMigrationsDirectory(string $migrationsDirectory) : void
    {
        $this->migrationsDirectory = $migrationsDirectory;
    }

    public function getMigrationsDirectory() : ?string
    {
        return $this->migrationsDirectory;
    }

    public function setMigrationsNamespace(string $migrationsNamespace) : void
    {
        $this->migrationsNamespace = $migrationsNamespace;
    }

    public function getMigrationsNamespace() : ?string
    {
        return $this->migrationsNamespace;
    }

    public function setCustomTemplate(?string $customTemplate) : void
    {
        $this->customTemplate = $customTemplate;
    }

    public function getCustomTemplate() : ?string
    {
        return $this->customTemplate;
    }

    /** @throws MigrationException */
    public function setMigrationsFinder(MigrationFinder $finder) : void
    {
        if (($this->migrationsAreOrganizedByYear || $this->migrationsAreOrganizedByYearAndMonth)
            && ! ($finder instanceof MigrationDeepFinder)) {
            throw MigrationException::configurationIncompatibleWithFinder(
                'organize-migrations',
                $finder
            );
        }

        $this->migrationFinder = $finder;
    }

    /** @return Version[] */
    public function registerMigrationsFromDirectory(string $path) : array
    {
        $this->validate();

        return $this->registerMigrations($this->findMigrations($path));
    }

    /** @throws MigrationException */
    public function registerMigration(string $version, string $class) : Version
    {
        $this->ensureMigrationClassExists($class);

        if (isset($this->migrations[$version])) {
            throw MigrationException::duplicateMigrationVersion(
                $version,
                get_class($this->migrations[$version])
            );
        }

        $version = new Version($this, $version, $class);

        $this->migrations[$version->getVersion()] = $version;

        ksort($this->migrations, SORT_STRING);

        return $version;
    }

    /**
     * @param string[] $migrations
     *
     * @return Version[]
     */
    public function registerMigrations(array $migrations) : array
    {
        $versions = [];

        foreach ($migrations as $version => $class) {
            $versions[] = $this->registerMigration((string) $version, $class);
        }

        return $versions;
    }

    /**
     * @return Version[]
     */
    public function getMigrations() : array
    {
        return $this->migrations;
    }

    public function getVersion(string $version) : Version
    {
        $this->loadMigrationsFromDirectory();

        if (! isset($this->migrations[$version])) {
            throw MigrationException::unknownMigrationVersion($version);
        }

        return $this->migrations[$version];
    }

    public function hasVersion(string $version) : bool
    {
        $this->loadMigrationsFromDirectory();

        return isset($this->migrations[$version]);
    }

    public function hasVersionMigrated(Version $version) : bool
    {
        $this->connect();
        $this->createMigrationTable();

        $version = $this->connection->fetchColumn(
            'SELECT ' . $this->getQuotedMigrationsColumnName() . ' FROM ' . $this->migrationsTableName . ' WHERE ' . $this->getQuotedMigrationsColumnName() . ' = ?',
            [$version->getVersion()]
        );

        return $version !== false;
    }

    /** @return string[] */
    public function getMigratedVersions() : array
    {
        $this->createMigrationTable();

        if (! $this->migrationTableCreated && $this->isDryRun) {
            return [];
        }

        $this->connect();

        $sql = sprintf(
            'SELECT %s FROM %s',
            $this->getQuotedMigrationsColumnName(),
            $this->migrationsTableName
        );

        $result = $this->connection->fetchAll($sql);

        return array_map('current', $result);
    }

    /** @return string[] */
    public function getAvailableVersions() : array
    {
        $availableVersions = [];

        $this->loadMigrationsFromDirectory();

        foreach ($this->migrations as $migration) {
            $availableVersions[] = $migration->getVersion();
        }

        return $availableVersions;
    }

    public function getCurrentVersion() : string
    {
        $this->createMigrationTable();

        if (! $this->migrationTableCreated && $this->isDryRun) {
            return '0';
        }

        $this->connect();

        $this->loadMigrationsFromDirectory();

        $where = null;

        if (! empty($this->migrations)) {
            $migratedVersions = [];

            foreach ($this->migrations as $migration) {
                $migratedVersions[] = sprintf("'%s'", $migration->getVersion());
            }

            $where = sprintf(
                ' WHERE %s IN (%s)',
                $this->getQuotedMigrationsColumnName(),
                implode(', ', $migratedVersions)
            );
        }

        $sql = sprintf(
            'SELECT %s FROM %s%s ORDER BY %s DESC',
            $this->getQuotedMigrationsColumnName(),
            $this->migrationsTableName,
            $where,
            $this->getQuotedMigrationsColumnName()
        );

        $sql    = $this->connection->getDatabasePlatform()->modifyLimitQuery($sql, 1);
        $result = $this->connection->fetchColumn($sql);

        return $result !== false ? (string) $result : '0';
    }

    public function getPrevVersion() : ?string
    {
        return $this->getRelativeVersion($this->getCurrentVersion(), -1);
    }

    public function getNextVersion() : ?string
    {
        return $this->getRelativeVersion($this->getCurrentVersion(), 1);
    }

    public function getRelativeVersion(string $version, int $delta) : ?string
    {
        $this->loadMigrationsFromDirectory();

        $versions = array_map('strval', array_keys($this->migrations));

        array_unshift($versions, '0');

        $offset = array_search($version, $versions, true);

        if ($offset === false || ! isset($versions[$offset + $delta])) {
            // Unknown version or delta out of bounds.
            return null;
        }

        return $versions[$offset + $delta];
    }

    public function getDeltaVersion(string $delta) : ?string
    {
        $symbol = substr($delta, 0, 1);
        $number = (int) substr($delta, 1);

        if ($number <= 0) {
            return null;
        }

        if ($symbol === '+' || $symbol === '-') {
            return $this->getRelativeVersion($this->getCurrentVersion(), (int) $delta);
        }

        return null;
    }

    /**
     * Returns the version number from an alias.
     *
     * Supported aliases are:
     *
     * - first: The very first version before any migrations have been run.
     * - current: The current version.
     * - prev: The version prior to the current version.
     * - next: The version following the current version.
     * - latest: The latest available version.
     *
     * If an existing version number is specified, it is returned verbatimly.
     */
    public function resolveVersionAlias(string $alias) : ?string
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
                if (substr($alias, 0, 7) === 'current') {
                    return $this->getDeltaVersion(substr($alias, 7));
                }

                return null;
        }
    }

    public function getNumberOfExecutedMigrations() : int
    {
        $this->connect();
        $this->createMigrationTable();

        $sql = sprintf(
            'SELECT COUNT(%s) FROM %s',
            $this->getQuotedMigrationsColumnName(),
            $this->migrationsTableName
        );

        $result = $this->connection->fetchColumn($sql);

        return $result !== false ? (int) $result : 0;
    }

    public function getNumberOfAvailableMigrations() : int
    {
        $this->loadMigrationsFromDirectory();

        return count($this->migrations);
    }

    public function getLatestVersion() : string
    {
        $this->loadMigrationsFromDirectory();

        $versions = array_keys($this->migrations);
        $latest   = end($versions);

        return $latest !== false ? (string) $latest : '0';
    }

    public function createMigrationTable() : bool
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

        $table = new Table($this->migrationsTableName, $columns);
        $table->setPrimaryKey([$this->migrationsColumnName]);

        $this->connection->getSchemaManager()->createTable($table);

        $this->migrationTableCreated = true;

        return true;
    }

    /** @return Version[] */
    public function getMigrationsToExecute(string $direction, string $to) : array
    {
        $this->loadMigrationsFromDirectory();

        if ($direction === Version::DIRECTION_DOWN) {
            if (count($this->migrations) !== 0) {
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
            if (! $this->shouldExecuteMigration($direction, $version, $to, $migrated)) {
                continue;
            }

            $versions[$version->getVersion()] = $version;
        }

        return $versions;
    }

    public function dispatchEvent(string $eventName, ?EventArgs $args = null) : void
    {
        $this->connection->getEventManager()->dispatchEvent($eventName, $args);
    }

    /**
     * @return string[]
     */
    protected function findMigrations(string $path) : array
    {
        return $this->migrationFinder->findMigrations($path, $this->getMigrationsNamespace());
    }

    /**
     * @throws MigrationException
     */
    public function setMigrationsAreOrganizedByYear(bool $migrationsAreOrganizedByYear = true) : void
    {
        $this->ensureOrganizeMigrationsIsCompatibleWithFinder();

        $this->migrationsAreOrganizedByYear = $migrationsAreOrganizedByYear;
    }

    /**
     * @throws MigrationException
     */
    public function setMigrationsAreOrganizedByYearAndMonth(bool $migrationsAreOrganizedByYearAndMonth = true) : void
    {
        $this->ensureOrganizeMigrationsIsCompatibleWithFinder();

        $this->migrationsAreOrganizedByYear         = $migrationsAreOrganizedByYearAndMonth;
        $this->migrationsAreOrganizedByYearAndMonth = $migrationsAreOrganizedByYearAndMonth;
    }

    public function generateVersionNumber(?DateTimeInterface $now = null) : string
    {
        $now = $now ?: new DateTime('now', new DateTimeZone('UTC'));

        return $now->format(self::VERSION_FORMAT);
    }

    /**
     * Explicitely opens the database connection. This is done to play nice
     * with DBAL's MasterSlaveConnection. Which, in some cases, connects to a
     * follower when fetching the executed migrations. If a follower is lagging
     * significantly behind that means the migrations system may see unexecuted
     * migrations that were actually executed earlier.
     */
    protected function connect() : bool
    {
        if ($this->connection instanceof MasterSlaveConnection) {
            return $this->connection->connect('master');
        }

        return $this->connection->connect();
    }

    /**
     * @throws MigrationException
     */
    private function ensureOrganizeMigrationsIsCompatibleWithFinder() : void
    {
        if (! ($this->migrationFinder instanceof MigrationDeepFinder)) {
            throw MigrationException::configurationIncompatibleWithFinder(
                'organize-migrations',
                $this->migrationFinder
            );
        }
    }

    /** @param string[] $migrated */
    private function shouldExecuteMigration(
        string $direction,
        Version $version,
        string $to,
        array $migrated
    ) : bool {
        $to = (int) $to;

        if ($direction === Version::DIRECTION_DOWN) {
            if (! in_array($version->getVersion(), $migrated, true)) {
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

        return false;
    }

    /** @throws MigrationException */
    private function ensureMigrationClassExists(string $class) : void
    {
        if (! class_exists($class)) {
            throw MigrationException::migrationClassNotFound(
                $class,
                $this->getMigrationsNamespace()
            );
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

    public function setIsDryRun(bool $isDryRun) : void
    {
        $this->isDryRun = $isDryRun;
    }

    private function loadMigrationsFromDirectory() : void
    {
        if (count($this->migrations) !== 0 || $this->migrationsDirectory === null) {
            return;
        }

        $this->registerMigrationsFromDirectory($this->migrationsDirectory);
    }

    private function getMigrationsColumn() : Column
    {
        return new Column(
            $this->migrationsColumnName,
            Type::getType('string'),
            ['length' => 255]
        );
    }
}
