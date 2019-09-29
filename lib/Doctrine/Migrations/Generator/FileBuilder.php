<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Generator;

use DateTimeImmutable;
use DateTimeInterface;
use function sprintf;

/**
 * The FileBuilder class is responsible for building a migration SQL file from an array of queries per version.
 *
 * @internal
 */
final class FileBuilder implements FileBuilderInterface
{
    /** @param string[][] $queriesByVersion */
    public function buildMigrationFile(
        array $queriesByVersion,
        string $direction,
        ?DateTimeInterface $now = null
    ) : string {
        $now    = $now ?: new DateTimeImmutable();
        $string = sprintf("-- Doctrine Migration File Generated on %s\n", $now->format('Y-m-d H:i:s'));

        foreach ($queriesByVersion as $version => $queries) {
            $version = (string) $version;

            $string .= "\n-- Version " . $version . "\n";

            foreach ($queries as $query) {
                $string .= $query . ";\n";
            }
        }

        return $string;
    }
}
