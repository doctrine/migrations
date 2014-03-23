<?php

namespace Doctrine\Tests\Migrations;

use Doctrine\Migrations\MigrationCollection;
use Doctrine\Migrations\MigrationStatus;
use Doctrine\Migrations\MigrationInfo;
use Doctrine\Migrations\Version;

class MigrationStatusTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_needs_repair_when_one_migration_not_executed_successfully()
    {
        $failedMigration = new MigrationInfo(new Version(1));
        $failedMigration->sucess = false;
        $executedMigrations = new MigrationCollection();
        $executedMigrations->add($failedMigration);

        $status = new MigrationStatus(
            $executedMigrations,
            new MigrationCollection(),
            true
        );

        $this->assertTrue($status->needsRepair());
    }
}
