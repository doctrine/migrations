<?php

namespace Doctrine\Tests\Migrations\Task;

use Doctrine\Migrations\Version;
use Doctrine\Migrations\MigrationInfo;
use Doctrine\Migrations\Task\Repair;
use Doctrine\Tests\Migrations\TestCase;

class RepairTest extends TestCase
{
    public function testNoopWhenNothingToRepair()
    {
        $storage = \Phake::mock('Doctrine\Migrations\MetadataStorage');

        $task = new Repair($storage);
        $task->execute($this->createInitializedMigrationStatus());

        \Phake::verifyNoInteraction($storage);
    }

    public function testRepairDeletesMigrationFromMetadata()
    {
        $storage = \Phake::mock('Doctrine\Migrations\MetadataStorage');

        $migration = new MigrationInfo(new Version('1.0'));
        $migration->success = false;

        $task = new Repair($storage);
        $task->execute($this->createMigrationStatus(array($migration)));

        \Phake::verify($storage)->delete($migration);
    }
}
