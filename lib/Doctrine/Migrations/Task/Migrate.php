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

    public function execute(MigrationStatus $status, $installedBy = null)
    {
        $this->assertValidToMigrate($status);

        $outstandingMigrations = $status->getOutstandingMigrations();

        $maxInstalledRank = $status->getMaxInstalledRank();

        foreach ($outstandingMigrations as $migration) {
            $executor = $this->executorRegistry->findFor($migration);

            $migration->installedRank = ++$maxInstalledRank;
            $migration->installedOn = new \DateTime('now');
            $migration->installedBy = $installedBy;
            $migration->success = false;

            $this->metadataStorage->start($migration);

            $start = microtime(true);
            try {
                $executor->execute($migration);

                $migration->success = true;
            } catch (\Exception $e) {
            }

            $migration->executionTime = round(microtime(true) - $start, 3) * 1000;

            $this->metadataStorage->complete($migration);

            if (!$migration->success) {
                throw new Exception\ExecutionFailedException($migration, $e);
            }
        }
    }

    private function assertValidToMigrate($status)
    {
        if ( ! $status->isInitialized()) {
            if ( ! $this->configuration->allowInitOnMigrate()) {
                throw new Exception\MetadataIsNotInitializedException();
            }

            $this->initMetadata($status);
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
    }

    private function initMetadata($status)
    {
        $task = new InitializeMetadata($this->metadataStorage);
        $task->execute($status);
    }
}
