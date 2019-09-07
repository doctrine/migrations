<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\Common\EventArgs;
use Doctrine\Migrations\Configuration\Exception\MigrationsNamespaceRequired;
use Doctrine\Migrations\Configuration\Exception\ParameterIncompatibleWithFinder;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\Exception\MigrationsDirectoryRequired;
use Doctrine\Migrations\Finder\MigrationDeepFinder;
use Doctrine\Migrations\Finder\MigrationFinder;
use function key;
use function reset;
use function str_replace;
use function strlen;

/**
 * The Configuration class is responsible for defining migration configuration information.
 */
class Configuration
{
    public const VERSIONS_ORGANIZATION_BY_YEAR = 'year';

    public const VERSIONS_ORGANIZATION_BY_YEAR_AND_MONTH = 'year_and_month';

    public const VERSION_FORMAT = 'YmdHis';

    /** @var string|null */
    private $name;

    /** @var string */
    private $migrationsTableName = 'doctrine_migration_versions';

    /** @var string */
    private $migrationsColumnName = 'version';

    /** @var int */
    private $migrationsColumnLength;

    /** @var string */
    private $migrationsExecutedAtColumnName = 'executed_at';

    /** @var string[][] */
    private $migrationsDirectories = [];

    /** @var bool */
    private $migrationsAreOrganizedByYear = false;

    /** @var bool */
    private $migrationsAreOrganizedByYearAndMonth = false;

    /** @var string|null */
    private $customTemplate;

    /** @var bool */
    private $isDryRun = false;

    /** @var bool */
    private $allOrNothing = false;

    /** @var DependencyFactory|null */
    private $dependencyFactory;

    /** @var bool */
    private $checkDbPlatform = true;


    public function addMigrationsDirectory(string $namespace, string $path) : void
    {
        $this->migrationsDirectories[$namespace] = $path;
    }

    public function getMigrationDirectories() : array
    {
        return $this->migrationsDirectories;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function getName() : ?string
    {
        return $this->name;
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

    public function setMigrationsColumnLength(int $columnLength) : void
    {
        $this->migrationsColumnLength = $columnLength;
    }

    public function getMigrationsColumnLength() : int
    {
        return $this->migrationsColumnLength;
    }

    public function setMigrationsExecutedAtColumnName(string $migrationsExecutedAtColumnName) : void
    {
        $this->migrationsExecutedAtColumnName = $migrationsExecutedAtColumnName;
    }

    public function getMigrationsExecutedAtColumnName() : string
    {
        return $this->migrationsExecutedAtColumnName;
    }


    public function setCustomTemplate(?string $customTemplate) : void
    {
        $this->customTemplate = $customTemplate;
    }

    public function getCustomTemplate() : ?string
    {
        return $this->customTemplate;
    }

    public function areMigrationsOrganizedByYear() : bool
    {
        return $this->migrationsAreOrganizedByYear;
    }

    /**
     * @throws MigrationException
     */
    public function setMigrationsAreOrganizedByYear(
        bool $migrationsAreOrganizedByYear = true
    ) : void {
        $this->ensureOrganizeMigrationsIsCompatibleWithFinder();

        $this->migrationsAreOrganizedByYear = $migrationsAreOrganizedByYear;
    }

    /**
     * @throws MigrationException
     */
    public function setMigrationsAreOrganizedByYearAndMonth(
        bool $migrationsAreOrganizedByYearAndMonth = true
    ) : void {
        $this->ensureOrganizeMigrationsIsCompatibleWithFinder();

        $this->migrationsAreOrganizedByYear         = $migrationsAreOrganizedByYearAndMonth;
        $this->migrationsAreOrganizedByYearAndMonth = $migrationsAreOrganizedByYearAndMonth;
    }

    public function areMigrationsOrganizedByYearAndMonth() : bool
    {
        return $this->migrationsAreOrganizedByYearAndMonth;
    }

    /** @throws MigrationException */
    public function setMigrationsFinder(MigrationFinder $migrationFinder) : void
    {
        if (($this->migrationsAreOrganizedByYear || $this->migrationsAreOrganizedByYearAndMonth)
            && ! ($migrationFinder instanceof MigrationDeepFinder)) {
            throw ParameterIncompatibleWithFinder::new(
                'organize-migrations',
                $migrationFinder
            );
        }

        $this->migrationFinder = $migrationFinder;
    }


    /** @throws MigrationException */
    public function validate() : void
    {
        if (empty($this->migrationsDirectories)) {
            throw MigrationsNamespaceRequired::new();
        }
    }


//    public function resolveVersionAlias(string $alias) : ?string
//    {
//        return $this->getDependencyFactory()->getVersionAliasResolver()->resolveVersionAlias($alias);
//    }

    public function setIsDryRun(bool $isDryRun) : void
    {
        $this->isDryRun = $isDryRun;
    }

    public function isDryRun() : bool
    {
        return $this->isDryRun;
    }

    public function setAllOrNothing(bool $allOrNothing) : void
    {
        $this->allOrNothing = $allOrNothing;
    }

    public function isAllOrNothing() : bool
    {
        return $this->allOrNothing;
    }

    public function setCheckDatabasePlatform(bool $checkDbPlatform) : void
    {
        $this->checkDbPlatform = $checkDbPlatform;
    }

    public function isDatabasePlatformChecked() : bool
    {
        return $this->checkDbPlatform;
    }

    public function getDateTime(string $version) : string
    {
        $datetime = str_replace('Version', '', $version);
        $datetime = DateTimeImmutable::createFromFormat(self::VERSION_FORMAT, $datetime);

        if ($datetime === false) {
            return '';
        }

        return $datetime->format('Y-m-d H:i:s');
    }

    public function generateVersionNumber(?DateTimeInterface $now = null) : string
    {
        $now = $now ?: $this->createDateTime();

        return $now->format(self::VERSION_FORMAT);
    }
//
//    public function dispatchEvent(string $eventName, ?EventArgs $args = null) : void
//    {
//        $this->getDependencyFactory()->getEventDispatcher()->dispatchEvent(
//            $eventName,
//            $args
//        );
//    }


//    public function getDependencyFactory() : DependencyFactory
//    {
//        if ($this->dependencyFactory === null) {
//            $this->dependencyFactory = new DependencyFactory($this);
//        }
//
//        return $this->dependencyFactory;
//    }

    /**
     * @throws MigrationException
     */
    private function ensureOrganizeMigrationsIsCompatibleWithFinder() : void
    {
        if (! ($this->getMigrationsFinder() instanceof MigrationDeepFinder)) {
            throw ParameterIncompatibleWithFinder::new(
                'organize-migrations',
                $this->getMigrationsFinder()
            );
        }
    }

    private function createDateTime() : DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
