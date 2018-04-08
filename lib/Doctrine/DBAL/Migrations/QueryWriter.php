<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations;

interface QueryWriter
{
    /**
     * @param string[][] $queriesByVersion
     */
    public function write(string $path, string $direction, array $queriesByVersion) : bool;
}
