<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Generator;

use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Migrations\Metadata\MetadataStorage;
use Doctrine\Migrations\Version\Direction;
use function sprintf;

/**
 * The FileBuilder class is responsible for building a migration SQL file from an array of queries per version.
 *
 * @internal
 */
final class FileBuilder
{
    /** @var AbstractPlatform */
    private $platform;

    /** @var MetadataStorage */
    private $metadataStorage;

    public function __construct(
        MetadataStorage $metadataStorage
    ) {
        $this->metadataStorage = $metadataStorage;
    }

    /** @param string[][] $queriesByVersion */
    public function buildMigrationFile(
        array $queriesByVersion,
        string $direction,
        DateTimeInterface $now
    ) : string {
        $string = sprintf("-- Doctrine Migration File Generated on %s\n", $now->format('Y-m-d H:i:s'));

        foreach ($queriesByVersion as $version => $queries) {
            $version = (string) $version;

            $string .= "\n-- Version " . $version . "\n";

            foreach ($queries as $query) {
                $string .= $query . ";\n";
            }

            // @todo this is missing
            // $string .= $this->getVersionUpdateQuery($version, $direction);
        }

        return $string;
    }

    private function getVersionUpdateQuery(string $version, string $direction) : string
    {
        if ($direction === Direction::DOWN) {
            return sprintf(
                "DELETE FROM %s WHERE %s = '%s';\n",
                $this->tableName,
                $this->columnName,
                $version
            );
        }

        return sprintf(
            "INSERT INTO %s (%s, %s) VALUES ('%s', %s);\n",
            $this->tableName,
            $this->columnName,
            $this->executedAtColumnName,
            $version,
            $this->platform->getCurrentTimestampSQL()
        );
    }
}
