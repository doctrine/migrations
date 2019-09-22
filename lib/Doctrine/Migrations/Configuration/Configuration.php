<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\Migrations\Configuration\Exception\MigrationsNamespaceRequired;
use Doctrine\Migrations\Configuration\Exception\UnknownConfigurationValue;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\Metadata\Storage\MetadataStorageConfigration;
use function count;
use function strcasecmp;

/**
 * The Configuration class is responsible for defining migration configuration information.
 */
class Configuration
{
    public const VERSIONS_ORGANIZATION_BY_YEAR           = 'year';
    public const VERSIONS_ORGANIZATION_BY_YEAR_AND_MONTH = 'year_and_month';
    public const VERSION_FORMAT                          = 'YmdHis';

    /** @var string|null */
    private $name;

    /** @var array<string, string> */
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

    /** @var bool */
    private $checkDbPlatform = true;

    /** @var MetadataStorageConfigration */
    private $metadataStorageConfiguration;

    public function setMetadataStorageConfiguration(MetadataStorageConfigration $metadataStorageConfiguration) : void
    {
        $this->metadataStorageConfiguration = $metadataStorageConfiguration;
    }

    public function getMetadataStorageConfiguration() : ?MetadataStorageConfigration
    {
        return $this->metadataStorageConfiguration;
    }

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
        $this->migrationsAreOrganizedByYear = $migrationsAreOrganizedByYear;
    }

    /**
     * @throws MigrationException
     */
    public function setMigrationsAreOrganizedByYearAndMonth(
        bool $migrationsAreOrganizedByYearAndMonth = true
    ) : void {
        $this->migrationsAreOrganizedByYear         = $migrationsAreOrganizedByYearAndMonth;
        $this->migrationsAreOrganizedByYearAndMonth = $migrationsAreOrganizedByYearAndMonth;
    }

    public function areMigrationsOrganizedByYearAndMonth() : bool
    {
        return $this->migrationsAreOrganizedByYearAndMonth;
    }

    /** @throws MigrationException */
    public function validate() : void
    {
        if (count($this->migrationsDirectories) === 0) {
            throw MigrationsNamespaceRequired::new();
        }
    }

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

    public function generateVersionNumber(?DateTimeInterface $now = null) : string
    {
        $now = $now ?: $this->createDateTime();

        return $now->format(self::VERSION_FORMAT);
    }

    private function createDateTime() : DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function setMigrationOrganization(string $migrationOrganization) : void
    {
        if (strcasecmp($migrationOrganization, self::VERSIONS_ORGANIZATION_BY_YEAR) === 0) {
            $this->setMigrationsAreOrganizedByYear();
        } elseif (strcasecmp($migrationOrganization, self::VERSIONS_ORGANIZATION_BY_YEAR_AND_MONTH) === 0) {
            $this->setMigrationsAreOrganizedByYearAndMonth();
        } else {
            throw UnknownConfigurationValue::new('organize_migrations', $migrationOrganization);
        }
    }
}
