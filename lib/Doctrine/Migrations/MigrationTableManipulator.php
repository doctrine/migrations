<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\Migrations\Configuration\Configuration;

/**
 * @internal
 */
class MigrationTableManipulator
{
    /** @var Configuration */
    private $configuration;

    /** @var AbstractSchemaManager */
    private $schemaManager;

    /** @var MigrationTable */
    private $migrationTable;

    /** @var MigrationTableStatus */
    private $migrationTableStatus;

    /** @var MigrationTableUpdater */
    private $migrationTableUpdater;

    public function __construct(
        Configuration $configuration,
        AbstractSchemaManager $schemaManager,
        MigrationTable $migrationTable,
        MigrationTableStatus $migrationTableStatus,
        MigrationTableUpdater $migrationTableUpdater
    ) {
        $this->configuration         = $configuration;
        $this->schemaManager         = $schemaManager;
        $this->migrationTable        = $migrationTable;
        $this->migrationTableStatus  = $migrationTableStatus;
        $this->migrationTableUpdater = $migrationTableUpdater;
    }

    public function createMigrationTable() : bool
    {
        $this->configuration->validate();

        if ($this->configuration->isDryRun()) {
            return false;
        }

        if ($this->migrationTableStatus->isCreated()) {
            if (! $this->migrationTableStatus->isUpToDate()) {
                $this->migrationTableUpdater->updateMigrationTable();

                $this->migrationTableStatus->setUpToDate(true);

                return true;
            }

            return false;
        }

        $table = $this->migrationTable->getNewDBALTable();

        $this->schemaManager->createTable($table);

        $this->migrationTableStatus->setCreated(true);

        return true;
    }
}
