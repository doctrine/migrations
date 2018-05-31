<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use Doctrine\Migrations\Migration;
use Doctrine\Migrations\MigratorConfiguration;

/**
 * @internal
 */
interface ExecutorInterface
{
    /**
     * @param mixed[] $params
     * @param mixed[] $types
     */
    public function addSql(string $sql, array $params = [], array $types = []) : void;

    public function execute(
        Version $version,
        Migration $migration,
        string $direction,
        ?MigratorConfiguration $migratorConfiguration = null
    ) : ExecutionResult;
}
