<?php

namespace Doctrine\Migrations\Task;

use Doctrine\Migrations\MetadataStorage;
use Doctrine\Migrations\MigrationStatus;

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
        if ( ! $this->metadataStorage->isInitialized()) {
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

        foreach ($outstandingMigrations as $migration) {
            $this->metadataStorage->start($migration);

            $executor = $this->executorRegistry->findFor($migration);
            $executor->execute($migration);

            $this->metadataStorage->complete($migration);
        }
    }
}
