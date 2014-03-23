<?php

namespace Doctrine\Tests\Migrations;

use Doctrine\Migrations\MigrationCollection;
use Doctrine\Migrations\MigrationStatus;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    public function createMigrationStatus(array $migrations = array(), array $outstanding = arraY())
    {
        $executed = new MigrationCollection($migrations);
        $outstanding = new MigrationCollection($outstanding);

        return new MigrationStatus(
            $executed,
            $outstanding,
            true
        );
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
