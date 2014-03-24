<?php

namespace Doctrine\Tests\Migrations;

use Doctrine\Migrations\MigrationSet;
use Doctrine\Migrations\Migrations;
use Doctrine\Migrations\Configuration;

class MigrationsTest extends \PHPUnit_Framework_TestCase
{
    public function testGetInfoDelegatesToStorage()
    {
        $configuration = new Configuration();
        $metadataStorage = \Phake::mock('Doctrine\Migrations\MetadataStorage');
        $loader = \Phake::mock('Doctrine\Migrations\Loader\Loader');
        $registry = \Phake::mock('Doctrine\Migrations\Executor\ExecutorRegistry');

        \Phake::when($metadataStorage)->isInitialized()->thenReturn(true);
        \Phake::when($metadataStorage)->getExecutedMigrations()->thenReturn(new MigrationSet());
        \Phake::when($loader)->load($configuration->getScriptDirectory())->thenReturn(new MigrationSet());

        $migrations = new Migrations($configuration, $metadataStorage, $loader, $registry);
        $status = $migrations->getInfo();

        $this->assertInstanceOf('Doctrine\Migrations\MigrationStatus', $status);
    }
}
