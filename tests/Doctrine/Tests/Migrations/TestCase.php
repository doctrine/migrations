<?php

namespace Doctrine\Tests\Migrations;

use Doctrine\Migrations\MigrationSet;
use Doctrine\Migrations\MigrationStatus;

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

    public function createConfiguration()
    {
        return new \Doctrine\Migrations\Configuration();
    }
}
