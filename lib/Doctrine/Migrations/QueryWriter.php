<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Query\Query;

/**
 * The QueryWriter defines the interface used for writing migration SQL queries to a file on disk.
 *
 * @internal
 */
interface QueryWriter
{
    /**
     * @param array<string,Query[]> $queriesByVersion
     */
    public function write(
        string $path,
        string $direction,
        array $queriesByVersion
    ): bool;
}
