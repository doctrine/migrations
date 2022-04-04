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
    private string $direction;
    private Version $version;
    private AbstractMigration $migration;
    public ?ExecutionResult $result = null;

    public function __construct(Version $version, AbstractMigration $migration, string $direction)
    {
        $this->version   = $version;
        $this->migration = $migration;
        $this->direction = $direction;
    }

    public function getVersion(): Version
    {
        return $this->version;
    }

    public function getResult(): ?ExecutionResult
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
