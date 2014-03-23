<?php

namespace Doctrine\Tests\Migrations;

use Doctrine\Migrations\MigrationCollection;
use Doctrine\Migrations\MigrationStatus;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    public function createMigrationStatus(array $migrations = array(), array $found = arraY())
    {
        $executed = new MigrationCollection($migrations);
        $found = new MigrationCollection($found);

        return new MigrationStatus($executed, $found, true);
    }

    public function createInitializedMigrationStatus()
    {
        return new MigrationStatus(
            new MigrationCollection(),
            new MigrationCollection(),
            true
        );
    }

    public function createUninitializedMigrationStatus()
    {
        return new MigrationStatus(
            new MigrationCollection(),
            new MigrationCollection(),
            false
        );
    }

    public function createConfiguration()
    {
        return new \Doctrine\Migrations\Configuration();
    }
}
