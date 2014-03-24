<?php

namespace Doctrine\Tests\Migrations;

use Doctrine\Migrations\MigrationSet;
use Doctrine\Migrations\MigrationStatus;
use Doctrine\Migrations\Version;
use Doctrine\Migrations\MigrationInfo;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    public function createMigrationStatus(array $migrations = array(), array $found = arraY())
    {
        $executed = new MigrationSet($migrations);
        $found = new MigrationSet($found);

        return new MigrationStatus($executed, $found, true);
    }

    public function createInitializedMigrationStatus()
    {
        return new MigrationStatus(
            new MigrationSet(),
            new MigrationSet(),
            true
        );
    }

    public function createUninitializedMigrationStatus()
    {
        return new MigrationStatus(
            new MigrationSet(),
            new MigrationSet(),
            false
        );
    }

    public function createMigrationInfo($version = null)
    {
        return new MigrationInfo(
            new Version($version ?: '1.0'),
            null,
            null,
            null,
            null
        );
    }

    public function createFailedMigration($version = null)
    {
        $migration = $this->createMigrationInfo($version);
        $migration->success = false;
        return $migration;
    }

    public function createSuccessMigration($version = null)
    {
        $migration = $this->createMigrationInfo($version);
        $migration->success = true;
        return $migration;
    }

    public function createConfiguration()
    {
        return new \Doctrine\Migrations\Configuration();
    }
}
