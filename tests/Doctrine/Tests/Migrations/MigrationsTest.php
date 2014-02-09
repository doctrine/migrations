<?php

namespace Doctrine\Tests\Migrations;

use Doctrine\Migrations\Migrations;
use Doctrine\Migrations\Configuration;

class MigrationsTest extends \PHPUnit_Framework_TestCase
{
    public function testInitMetadataThrowsExceptionWhenAlreadyInitialized()
    {
        $configuration = new Configuration();
        $metadataStorage = \Phake::mock('Doctrine\Migrations\MetadataStorage');

        \Phake::when($metadataStorage)->isInitialized()->thenReturn(true);

        $this->setExpectedException('Doctrine\Migrations\Exception\MetadataAlreadyInitializedException');

        $migrations = new Migrations($configuration, $metadataStorage);
        $migrations->initMetadata();
    }

    public function testInitMetadata()
    {
        $configuration = new Configuration();
        $metadataStorage = \Phake::mock('Doctrine\Migrations\MetadataStorage');

        \Phake::when($metadataStorage)->isInitialized()->thenReturn(false);

        $migrations = new Migrations($configuration, $metadataStorage);
        $migrations->initMetadata();

        \Phake::verify($metadataStorage)->initialize();
    }

    public function testGetInfoDelegatesToStorage()
    {
        $configuration = new Configuration();
        $metadataStorage = \Phake::mock('Doctrine\Migrations\MetadataStorage');

        \Phake::when($metadataStorage)->isInitialized()->thenReturn(true);
        \Phake::when($metadataStorage)->getExecutedMigrations()->thenReturn(array());

        $migrations = new Migrations($configuration, $metadataStorage);
        $status = $migrations->getInfo();

        $this->assertInstanceOf('Doctrine\Migrations\MigrationStatus', $status);
    }
}
