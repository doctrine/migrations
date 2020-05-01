<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Version;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\FilesystemMigrationsRepository;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsSet;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Version\CurrentMigrationStatusCalculator;
use Doctrine\Migrations\Version\MigrationStatusCalculator;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class MigrationStatusCalculatorTest extends TestCase
{
    /** @var MigrationStatusCalculator */
    private $migrationStatusCalculator;

    /** @var MockObject|MigrationsRepository */
    private $migrationRepository;

    /** @var MockObject|MetadataStorage */
    private $metadataStorage;

    /** @var MockObject|AbstractMigration */
    private $abstractMigration;

    protected function setUp() : void
    {
        $this->abstractMigration = $this->createMock(AbstractMigration::class);

        $this->migrationRepository       = $this->createMock(FilesystemMigrationsRepository::class);
        $this->metadataStorage           = $this->createMock(MetadataStorage::class);
        $this->migrationStatusCalculator = new CurrentMigrationStatusCalculator($this->migrationRepository, $this->metadataStorage);
    }

    public function testGetNewMigrations() : void
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $e1 = new ExecutedMigration(new Version('A'));

        $this->migrationRepository
            ->expects(self::any())
            ->method('getMigrations')
            ->willReturn(new AvailableMigrationsList([$m1, $m2, $m3]));

        $this->metadataStorage
            ->expects(self::atLeastOnce())
            ->method('getExecutedMigrations')
            ->willReturn(new ExecutedMigrationsSet([$e1]));

        $newSet = $this->migrationStatusCalculator->getNewMigrations();

        self::assertSame([$m2, $m3], $newSet->getItems());
    }

    public function testGetExecutedUnavailableMigrations() : void
    {
        $a1 = new AvailableMigration(new Version('A'), $this->abstractMigration);

        $e1 = new ExecutedMigration(new Version('A'));
        $e2 = new ExecutedMigration(new Version('B'));
        $e3 = new ExecutedMigration(new Version('C'));

        $this->migrationRepository
            ->expects(self::any())
            ->method('getMigrations')
            ->willReturn(new AvailableMigrationsList([$a1]));

        $this->metadataStorage
            ->expects(self::atLeastOnce())
            ->method('getExecutedMigrations')
            ->willReturn(new ExecutedMigrationsSet([$e1, $e2, $e3]));

        $newSet = $this->migrationStatusCalculator->getExecutedUnavailableMigrations();

        self::assertSame([$e2, $e3], $newSet->getItems());
    }
}
