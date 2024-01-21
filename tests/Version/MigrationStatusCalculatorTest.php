<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Version;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Version\CurrentMigrationStatusCalculator;
use Doctrine\Migrations\Version\MigrationPlanCalculator;
use Doctrine\Migrations\Version\MigrationStatusCalculator;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class MigrationStatusCalculatorTest extends TestCase
{
    private MigrationStatusCalculator $migrationStatusCalculator;

    /** @var MigrationPlanCalculator&MockObject */
    private MigrationPlanCalculator $migrationPlanCalculator;

    /** @var MetadataStorage&MockObject */
    private MetadataStorage $metadataStorage;

    /** @var AbstractMigration&MockObject */
    private AbstractMigration $abstractMigration;

    protected function setUp(): void
    {
        $this->abstractMigration       = $this->createMock(AbstractMigration::class);
        $this->metadataStorage         = $this->createMock(MetadataStorage::class);
        $this->migrationPlanCalculator = $this->createMock(MigrationPlanCalculator::class);

        $this->migrationStatusCalculator = new CurrentMigrationStatusCalculator($this->migrationPlanCalculator, $this->metadataStorage);
    }

    public function testGetNewMigrations(): void
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $e1 = new ExecutedMigration(new Version('A'));

        $this->migrationPlanCalculator
            ->expects(self::any())
            ->method('getMigrations')
            ->willReturn(new AvailableMigrationsList([$m1, $m2, $m3]));

        $this->metadataStorage
            ->expects(self::atLeastOnce())
            ->method('getExecutedMigrations')
            ->willReturn(new ExecutedMigrationsList([$e1]));

        $newSet = $this->migrationStatusCalculator->getNewMigrations();

        self::assertSame([$m2, $m3], $newSet->getItems());
    }

    public function testGetExecutedUnavailableMigrations(): void
    {
        $a1 = new AvailableMigration(new Version('A'), $this->abstractMigration);

        $e1 = new ExecutedMigration(new Version('A'));
        $e2 = new ExecutedMigration(new Version('B'));
        $e3 = new ExecutedMigration(new Version('C'));

        $this->migrationPlanCalculator
            ->expects(self::any())
            ->method('getMigrations')
            ->willReturn(new AvailableMigrationsList([$a1]));

        $this->metadataStorage
            ->expects(self::atLeastOnce())
            ->method('getExecutedMigrations')
            ->willReturn(new ExecutedMigrationsList([$e1, $e2, $e3]));

        $newSet = $this->migrationStatusCalculator->getExecutedUnavailableMigrations();

        self::assertSame([$e2, $e3], $newSet->getItems());
    }
}
