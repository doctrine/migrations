<?php

namespace Doctrine\Tests\Migrations\DBAL;

use Doctrine\Tests\Migrations\TestCase;
use Doctrine\Migrations\DBAL\Factory;

class IntegrationTest extends TestCase
{
    /**
     * @test
     */
    public function it_executes_dbal_migrations()
    {
        $factory = new Factory();
        $migrations = $factory->createFromArray(array(
            'db' => array(
                'driver' => 'pdo_sqlite',
                'memory' => true
            ),
            'migrations' => array(
                'script_directory' => __DIR__ . '/_files'
            )
        ));

        $status = $migrations->getInfo();

        $this->assertFalse($status->isInitialized(), "Metadata is not initialized.");
        $this->assertCount(0, $status->getExecutedMigrations());
        $this->assertCount(2, $status->getOutstandingMigrations());

        $migrations->initializeMetadata();

        $status = $migrations->getInfo();

        $this->assertTrue($status->isInitialized(), "Metadata is initialized.");

        $migrations->migrate();

        $status = $migrations->getInfo();

        $this->assertCount(2, $status->getExecutedMigrations());
        $this->assertCount(0, $status->getOutstandingMigrations());

        $metadataStorage = $this->readProperty($migrations, 'metadataStorage');
        $connection = $this->readProperty($metadataStorage, 'connection');

        $message = $connection->fetchColumn('SELECT val FROM test');
        $this->assertEquals('Hello World!', $message);
    }

    private function readProperty($object, $propertyName)
    {
        $reflection = new \ReflectionObject($object);
        $reflProperty = $reflection->getProperty($propertyName);
        $reflProperty->setAccessible(true);

        return $reflProperty->getValue($object);
    }
}
