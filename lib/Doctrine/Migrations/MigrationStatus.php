<?php

namespace Doctrine\Migrations;

class MigrationStatus
{
    private $executedMigrations = array();
    private $foundMigrations = array();
    private $metadataInitialized = false;

    /**
     * @param array<Doctrine\Migrations\MigrationInfo> $executedMigrations
     * @param array<Doctrine\Migrations\MigrationInfo> $foundMigrations
     * @param bool $metadataInitialized
     */
    public function __construct(array $executedMigrations, array $foundMigrations, $metadataInitialized)
    {
        $this->executedMigrations = $executedMigrations;
        $this->foundMigrations = $foundMigrations;
        $this->metadataInitialized = $metadataInitialized;
    }
}
