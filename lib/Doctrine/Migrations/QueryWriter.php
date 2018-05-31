<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

/**
 * @internal
 */
interface QueryWriter
{
    /**
     * @param string[][] $queriesByVersion
     */
    public function write(
        string $path,
        string $direction,
        array $queriesByVersion
    ) : bool;
}
