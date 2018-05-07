<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use DateTimeInterface;
use function sprintf;

/**
 * @var internal
 */
final class MigrationFileBuilder
{
    /** @var string */
    private $tableName;

    /** @var string */
    private $columnName;

    public function __construct(
        string $tableName,
        string $columnName
    ) {
        $this->columnName = $columnName;
        $this->tableName  = $tableName;
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

            $string .= $this->getVersionUpdateQuery($version, $direction);
        }

        return $string;
    }

    private function getVersionUpdateQuery(string $version, string $direction) : string
    {
        if ($direction === Version::DIRECTION_DOWN) {
            $query = "DELETE FROM %s WHERE %s = '%s';\n";
        } else {
            $query = "INSERT INTO %s (%s) VALUES ('%s');\n";
        }

        return sprintf(
            $query,
            $this->tableName,
            $this->columnName,
            $version
        );
    }
}
