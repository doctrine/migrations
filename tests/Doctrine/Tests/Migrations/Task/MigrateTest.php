<?php

namespace Doctrine\Tests\Migrations\Task;

use Doctrine\Tests\Migrations\TestCase;
use Doctrine\Migrations\Task\Migrate;
use Doctrine\Migrations\Version;
use Doctrine\Migrations\MigrationInfo;

class MigrateTest extends TestCase
{
    public function testExceptionWhenNotInitializedAndNotAllowedToMigrate()
    {
        $storage = \Phake::mock('Doctrine\Migrations\MetadataStorage');
        $registry = \Phake::mock('Doctrine\Migrations\Executor\ExecutorRegistry');
        $configuration = $this->createConfiguration();
        $configuration->setAllowInitOnMigrate(false);

        $this->setExpectedException('Doctrine\Migrations\Exception\MetadataIsNotInitializedException');

        $task = new Migrate($configuration, $storage, $registry);
        $task->execute($this->createUninitializedMigrationStatus());
    }

    public function testExceptionWhenNeedsRepair()
    {
        $storage = \Phake::mock('Doctrine\Migrations\MetadataStorage');
        $registry = \Phake::mock('Doctrine\Migrations\Executor\ExecutorRegistry');
        $configuration = $this->createConfiguration();
        $migration = new MigrationInfo(new Version('1.0'));
        $migration->setSuccess(false);

        $this->setExpectedException('Doctrine\Migrations\Exception\RepairNecessaryException');

        $task = new Migrate($configuration, $storage, $registry);
        $task->execute($this->createMigrationStatus(array($migration)));
    }

    public function testExecuteMigrations()
    {
        $storage = \Phake::mock('Doctrine\Migrations\MetadataStorage');
        $registry = \Phake::mock('Doctrine\Migrations\Executor\ExecutorRegistry');
        $executor = \Phake::mock('Doctrine\Migrations\Executor\Executor');

        \Phake::when($registry)
            ->findFor($this->isInstanceOf('Doctrine\Migrations\MigrationCollection'))
            ->thenReturn(array($executor));

        $configuration = $this->createConfiguration();
        $migration = new MigrationInfo(new Version('1.0'));
        $migration->setSuccess(true);

        \Phake::when($executor)->getMigration()->thenReturn($migration);

        $task = new Migrate($configuration, $storage, $registry);
        $task->execute($this->createMigrationStatus(array($migration)));

        \Phake::verify($executor)->execute($migration);
    }
}
