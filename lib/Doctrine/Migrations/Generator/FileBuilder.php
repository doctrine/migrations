<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Generator;

use DateTimeInterface;

/**
 * The ConcatenationFileBuilder class is responsible for building a migration SQL file from an array of queries per version.
 *
 * @internal
 */
interface FileBuilder
{
    /** @param string[][] $queriesByVersion */
    public function buildMigrationFile(array $queriesByVersion, string $direction, ?DateTimeInterface $now = null) : string;
}
