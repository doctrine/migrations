<?php

namespace Doctrine\Migrations\Task;

use Doctrine\Migrations\MetadataStorage;
use Doctrine\Migrations\MigrationStatus;
use Doctrine\Migrations\Exception;

class InitializeMetadata
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
        if ($status->isInitialized()) {
            throw new Exception\MetadataAlreadyInitializedException();
        }

        $this->metadataStorage->initialize();
    }
}
