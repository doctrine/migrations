<?php

namespace Doctrine\Tests\Migrations\Task;

use Doctrine\Tests\Migrations\TestCase;
use Doctrine\Migrations\Task\Migrate;

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
        $migration = $this->createMigrationInfo('1.0');
        $migration->success = false;

        $this->setExpectedException('Doctrine\Migrations\Exception\RepairNecessaryException');

        $task = new Migrate($configuration, $storage, $registry);
        $task->execute($this->createMigrationStatus(array($migration)));
    }

    public function testExecuteMigrations()
    {
        $storage = \Phake::mock('Doctrine\Migrations\MetadataStorage');
        $registry = \Phake::mock('Doctrine\Migrations\Executor\ExecutorRegistry');
        $executor = \Phake::mock('Doctrine\Migrations\Executor\Executor');

        $configuration = $this->createConfiguration();
        $migration = $this->createMigrationInfo('1.0');

        \Phake::when($registry)
            ->findFor($migration)
            ->thenReturn($executor);

        $task = new Migrate($configuration, $storage, $registry);
        $task->execute($this->createMigrationStatus(array(), array($migration)));

        \Phake::verify($executor)->execute($migration);
    }
}
