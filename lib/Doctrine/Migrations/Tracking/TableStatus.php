<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tracking;

use Doctrine\DBAL\Schema\AbstractSchemaManager;

/**
 * The TableStatus class is responsible for checking if the tracking table needs to be created or updated.
 *
 * @internal
 */
class TableStatus
{
    /** @var AbstractSchemaManager */
    private $schemaManager;

    /** @var TableDefinition */
    private $migrationTable;

    /** @var bool|null */
    private $created;

    public function __construct(
        AbstractSchemaManager $schemaManager,
        TableDefinition $migrationTable
    ) {
        $this->schemaManager  = $schemaManager;
        $this->migrationTable = $migrationTable;
    }

    public function setCreated(bool $created) : void
    {
        $this->created = $created;
    }

    public function isCreated() : bool
    {
        if ($this->created !== null) {
            return $this->created;
        }

        $this->created = $this->schemaManager->tablesExist([$this->migrationTable->getName()]);

        return $this->created;
    }
}
