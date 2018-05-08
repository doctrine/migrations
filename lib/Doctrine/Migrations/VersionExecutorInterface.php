<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

/**
 * @internal
 */
interface VersionExecutorInterface
{
    /**
     * @param mixed[] $params
     * @param mixed[] $types
     */
    public function addSql(string $sql, array $params = [], array $types = []) : void;

    public function execute(
        Version $version,
        AbstractMigration $migration,
        string $direction,
        bool $dryRun = false,
        bool $timeAllQueries = false
    ) : VersionExecutionResult;
}
