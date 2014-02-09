<?php

namespace Doctrine\Tests\Migrations;

use Doctrine\Migrations\MigrationCollection;
use Doctrine\Migrations\MigrationStatus;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
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
}
