<?php

namespace Doctrine\Tests\Migrations;

use Doctrine\Migrations\MigrationCollection;
use Doctrine\Migrations\Migrations;
use Doctrine\Migrations\Configuration;

class MigrationsTest extends \PHPUnit_Framework_TestCase
{
    public function testGetInfoDelegatesToStorage()
    {
        $configuration = new Configuration();
        $metadataStorage = \Phake::mock('Doctrine\Migrations\MetadataStorage');

        \Phake::when($metadataStorage)->isInitialized()->thenReturn(true);
        \Phake::when($metadataStorage)->getExecutedMigrations()->thenReturn(new MigrationCollection());

        $migrations = new Migrations($configuration, $metadataStorage);
        $status = $migrations->getInfo();

        $this->assertInstanceOf('Doctrine\Migrations\MigrationStatus', $status);
    }
}
