<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Generator;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Migrations\Query\Query;

use function sprintf;

/**
 * The ConcatenationFileBuilder class is responsible for building a migration SQL file from an array of queries per version.
 *
 * @internal
 */
final class ConcatenationFileBuilder implements FileBuilder
{
    /** @param array<string,Query[]> $queriesByVersion */
    public function buildMigrationFile(
        array $queriesByVersion,
        string $direction,
        DateTimeInterface|null $now = null,
    ): string {
        $now  ??= new DateTimeImmutable();
        $string = sprintf("-- Doctrine Migration File Generated on %s\n", $now->format('Y-m-d H:i:s'));

        foreach ($queriesByVersion as $version => $queries) {
            $string .= "\n-- Version " . $version . "\n";

            foreach ($queries as $query) {
                $string .= $query->getStatement() . ";\n";
            }
        }

        return $string;
    }
}
