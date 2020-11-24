<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration;

use Doctrine\Migrations\Configuration\Exception\FrozenConfiguration;
use Doctrine\Migrations\Configuration\Exception\UnknownConfigurationValue;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\Metadata\Storage\MetadataStorageConfiguration;

use function strtolower;

/**
 * The Configuration class is responsible for defining migration configuration information.
 */
final class Configuration
{
    public const VERSIONS_ORGANIZATION_NONE              = 'none';
    public const VERSIONS_ORGANIZATION_BY_YEAR           = 'year';
    public const VERSIONS_ORGANIZATION_BY_YEAR_AND_MONTH = 'year_and_month';

    /** @var array<string, string> */
    private $migrationsDirectories = [];

    /** @var string[] */
    private $migrationClasses = [];

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

    /** @var MetadataStorageConfiguration */
    private $metadataStorageConfiguration;

    /** @var bool */
    private $frozen = false;

    public function freeze(): void
    {
        $this->frozen = true;
    }

    private function assertNotFrozen(): void
    {
        if ($this->frozen) {
            throw FrozenConfiguration::new();
        }
    }

    public function setMetadataStorageConfiguration(MetadataStorageConfiguration $metadataStorageConfiguration): void
    {
        $this->assertNotFrozen();
        $this->metadataStorageConfiguration = $metadataStorageConfiguration;
    }

    /**
     * @return string[]
     */
    public function getMigrationClasses(): array
    {
        return $this->migrationClasses;
    }

    public function addMigrationClass(string $className): void
    {
        $this->assertNotFrozen();
        $this->migrationClasses[] = $className;
    }

    public function getMetadataStorageConfiguration(): ?MetadataStorageConfiguration
    {
        return $this->metadataStorageConfiguration;
    }

    public function addMigrationsDirectory(string $namespace, string $path): void
    {
        $this->assertNotFrozen();
        $this->migrationsDirectories[$namespace] = $path;
    }

    /**
     * @return array<string,string>
     */
    public function getMigrationDirectories(): array
    {
        return $this->migrationsDirectories;
    }

    public function setCustomTemplate(?string $customTemplate): void
    {
        $this->assertNotFrozen();
        $this->customTemplate = $customTemplate;
    }

    public function getCustomTemplate(): ?string
    {
        return $this->customTemplate;
    }

    public function areMigrationsOrganizedByYear(): bool
    {
        return $this->migrationsAreOrganizedByYear;
    }

    /**
     * @throws MigrationException
     */
    public function setMigrationsAreOrganizedByYear(
        bool $migrationsAreOrganizedByYear = true
    ): void {
        $this->assertNotFrozen();
        $this->migrationsAreOrganizedByYear = $migrationsAreOrganizedByYear;
    }

    /**
     * @throws MigrationException
     */
    public function setMigrationsAreOrganizedByYearAndMonth(
        bool $migrationsAreOrganizedByYearAndMonth = true
    ): void {
        $this->assertNotFrozen();
        $this->migrationsAreOrganizedByYear         = $migrationsAreOrganizedByYearAndMonth;
        $this->migrationsAreOrganizedByYearAndMonth = $migrationsAreOrganizedByYearAndMonth;
    }

    public function areMigrationsOrganizedByYearAndMonth(): bool
    {
        return $this->migrationsAreOrganizedByYearAndMonth;
    }

    public function setIsDryRun(bool $isDryRun): void
    {
        $this->assertNotFrozen();
        $this->isDryRun = $isDryRun;
    }

    public function isDryRun(): bool
    {
        return $this->isDryRun;
    }

    public function setAllOrNothing(bool $allOrNothing): void
    {
        $this->assertNotFrozen();
        $this->allOrNothing = $allOrNothing;
    }

    public function isAllOrNothing(): bool
    {
        return $this->allOrNothing;
    }

    public function setCheckDatabasePlatform(bool $checkDbPlatform): void
    {
        $this->checkDbPlatform = $checkDbPlatform;
    }

    public function isDatabasePlatformChecked(): bool
    {
        return $this->checkDbPlatform;
    }

    public function setMigrationOrganization(string $migrationOrganization): void
    {
        $this->assertNotFrozen();

        switch (strtolower($migrationOrganization)) {
            case self::VERSIONS_ORGANIZATION_NONE:
                $this->setMigrationsAreOrganizedByYearAndMonth(false);
                break;
            case self::VERSIONS_ORGANIZATION_BY_YEAR:
                $this->setMigrationsAreOrganizedByYear();
                break;
            case self::VERSIONS_ORGANIZATION_BY_YEAR_AND_MONTH:
                $this->setMigrationsAreOrganizedByYearAndMonth();
                break;
            default:
                throw UnknownConfigurationValue::new('organize_migrations', $migrationOrganization);
        }
    }
}
