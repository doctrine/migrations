<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use function date;
use function file_put_contents;
use function is_dir;
use function sprintf;

final class FileQueryWriter implements QueryWriter
{
    /** @var string */
    private $columnName;

    /** @var string */
    private $tableName;

    /** @var null|OutputWriter */
    private $outputWriter;

    public function __construct(
        string $columnName,
        string $tableName,
        ?OutputWriter $outputWriter
    ) {
        $this->columnName   = $columnName;
        $this->tableName    = $tableName;
        $this->outputWriter = $outputWriter;
    }

    /**
     * @param mixed[] $queriesByVersion
     */
    public function write(
        string $path,
        string $direction,
        array $queriesByVersion
    ) : bool {
        $path   = $this->buildMigrationFilePath($path);
        $string = $this->buildMigrationFile($queriesByVersion, $direction);

        if ($this->outputWriter !== null) {
            $this->outputWriter->write(
                "\n" . sprintf('Writing migration file to "<info>%s</info>"', $path)
            );
        }

        return file_put_contents($path, $string) !== false;
    }

    /** @param string[][] $queriesByVersion */
    private function buildMigrationFile(
        array $queriesByVersion,
        string $direction
    ) : string {
        $string = sprintf("-- Doctrine Migration File Generated on %s\n", date('Y-m-d H:i:s'));

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

    private function buildMigrationFilePath(string $path) : string
    {
        if (is_dir($path)) {
            $path = realpath($path);
            $path = $path . '/doctrine_migration_' . date('YmdHis') . '.sql';
        }

        return $path;
    }
}
