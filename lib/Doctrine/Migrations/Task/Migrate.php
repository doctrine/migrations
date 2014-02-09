<?php

namespace Doctrine\Migrations\Task;

use Doctrine\Migrations\Configuration;
use Doctrine\Migrations\MetadataStorage;
use Doctrine\Migrations\MigrationStatus;
use Doctrine\Migrations\Executor\ExecutorRegistry;
use Doctrine\Migrations\Exception;

class Migrate
{
    /**
     * @var \Doctrine\Migrations\Configuration
     */
    private $configuration;

    /**
     * @var \Doctrine\Migrations\MetadataStorage
     */
    private $metadataStorage;

    /**
     * @var \Doctrine\Migrations\ExecutorRegistry
     */
    private $executorRegistry;

    public function __construct(Configuration $configuration, MetadataStorage $metadataStorage, ExecutorRegistry $executorRegistry)
    {
        $this->configuration = $configuration;
        $this->metadataStorage = $metadataStorage;
        $this->executorRegistry = $executorRegistry;
    }

    public function execute(MigrationStatus $status)
    {
        if ( ! $status->isInitialized()) {
            if ( ! $this->configuration->allowInitOnMigrate()) {
                throw new Exception\MetadataIsNotInitializedException();
            }

            $this->initMetadata();
        }

        if ($this->configuration->validateOnMigrate() &&
            ! $status->areChecksumsValid()) {
            throw new Exception\InvalidChecksumException();
        }

        if ($status->needsRepair()) {
            throw new Exception\RepairNecessaryException();
        }

        if ($status->containsOutOfOrderMigrations() &&
            ! $this->configuration->outOfOrderMigrationsAllowed()) {
            throw new Exception\OutOfOrderMigrationsNotAllowedException();
        }

        $outstandingMigrations = $status->getOutstandingMigrations();
        $executors = $this->executorRegistry->findFor($outstandingMigrations);

        foreach ($executors as $executor) {
            $migration = $executor->getMigration();

            $this->metadataStorage->start($migration);
            $executor->execute($migration);
            $this->metadataStorage->complete($migration);
        }
    }
}
