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
        $task->execute($this->createInitializedMigrationStatus());
    }
}
