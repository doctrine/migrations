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
        $failedMigration = $this->createMigrationInfo('1.0');
        $failedMigration->sucess = false;
        $executedMigrations = new MigrationSet();
        $executedMigrations->add($failedMigration);

        $status = new MigrationStatus(
            $executedMigrations,
            new MigrationSet(),
            true
        );

        $this->assertTrue($status->needsRepair());
    }
}
