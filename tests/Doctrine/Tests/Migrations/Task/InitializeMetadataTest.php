<?php

namespace Doctrine\Tests\Migrations\Task;

use Doctrine\Migrations\Task\InitializeMetadata;
use Doctrine\Tests\Migrations\TestCase;

class InitializeMetadataTest extends TestCase
{
    public function testExceptionWhenMetadataInitialized()
    {
        $storage = \Phake::mock('Doctrine\Migrations\MetadataStorage');
        $task = new InitializeMetadata($storage);

        $this->setExpectedException('Doctrine\Migrations\Exception\MetadataAlreadyInitializedException');

        $task->execute($this->createInitializedMigrationStatus());
    }

    public function testInitialize()
    {
        $storage = \Phake::mock('Doctrine\Migrations\MetadataStorage');
        $task = new InitializeMetadata($storage);

        $task->execute($this->createUninitializedMigrationStatus());

        \Phake::verify($storage)->initialize();
    }
}
