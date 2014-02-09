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

    public function testGetInfoDelegatesToStoragE()
    {
        $configuration = new Configuration();
        $metadataStorage = \Phake::mock('Doctrine\Migrations\MetadataStorage');

        $migrations = new Migrations($configuration, $metadataStorage);
        $migrations->getInfo();

        \Phake::verify($metadataStorage)->getInfo();
    }
}
