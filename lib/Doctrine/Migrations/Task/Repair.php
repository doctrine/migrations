<?php

namespace Doctrine\Migrations\Task;

use Doctrine\Migrations\MetadataStorage;
use Doctrine\Migrations\MigrationStatus;

class Repair
{
    /**
     * @var \Doctrine\Migrations\MetadataStorage
     */
    private $metadataStorage;

    public function __construct(MetadataStorage $metadataStorage)
    {
        $this->metadataStorage = $metadataStorage;
    }

    public function execute(MigrationStatus $status)
    {
        if ( ! $status->needsRepair()) {
            return;
        }

        foreach ($status->getExecutedMigrations() as $migration) {
            if ( ! $migration->wasSuccessfullyExecuted()) {
                $this->metadataStorage->delete($migration);
            }
        }
    }
}
