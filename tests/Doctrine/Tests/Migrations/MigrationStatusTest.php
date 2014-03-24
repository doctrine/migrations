<?php

namespace Doctrine\Tests\Migrations;

use Doctrine\Migrations\MigrationSet;
use Doctrine\Migrations\MigrationStatus;
use Doctrine\Migrations\MigrationInfo;
use Doctrine\Migrations\Version;

class MigrationStatusTest extends TestCase
{
    /**
     * @test
     */
    public function it_needs_repair_when_one_migration_not_executed_successfully()
    {
        $failedMigration = $this->createFailedMigration('1.0');
        $executedMigrations = new MigrationSet();
        $executedMigrations->add($failedMigration);

        $status = new MigrationStatus(
            $executedMigrations,
            new MigrationSet(),
            true
        );

        $this->assertTrue($status->needsRepair());
    }

    /**
     * @test
     */
    public function it_detects_outstanding_migrations()
    {
        $status = new MigrationStatus(
            new MigrationSet(array(
                $executedMigration1 = $this->createSuccessMigration('1'),
                $executedMigration2 = $this->createSuccessMigration('2'),
            )),
            new MigrationSet(array(
                $executedMigration1,
                $executedMigration2,
                $outstandingMigration1 = $this->createMigrationInfo('3'),
                $outstandingMigration2 = $this->createMigrationInfo('4'),
            )),
            true
        );

        $outstanding = $status->getOutstandingMigrations();
        $this->assertFalse($outstanding->contains($executedMigration1));
        $this->assertFalse($outstanding->contains($executedMigration2));
        $this->assertTrue($outstanding->contains($outstandingMigration1));
        $this->assertTrue($outstanding->contains($outstandingMigration2));
    }
}
