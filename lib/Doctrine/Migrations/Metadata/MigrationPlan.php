<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\PlanAlreadyExecuted;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;

/**
 * Represents an available migration to be executed in a specific direction.
 */
final class MigrationPlan
{
    public ExecutionResult|null $result = null;

    public function __construct(
        private readonly Version $version,
        private readonly AbstractMigration $migration,
        private readonly string $direction,
    ) {
    }

    public function getVersion(): Version
    {
        return $this->version;
    }

    public function getResult(): ExecutionResult|null
    {
        return $this->result;
    }

    public function markAsExecuted(ExecutionResult $result): void
    {
        if ($this->result !== null) {
            throw PlanAlreadyExecuted::new();
        }

        $this->result = $result;
    }

    public function getMigration(): AbstractMigration
    {
        return $this->migration;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }
}
