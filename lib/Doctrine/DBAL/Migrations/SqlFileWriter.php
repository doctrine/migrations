<?php

namespace Doctrine\DBAL\Migrations;

use Doctrine\DBAL\Exception\InvalidArgumentException;

/**
 * @deprecated
 *
 * @see \Doctrine\DBAL\Migrations\FileQueryWriter
 */
class SqlFileWriter
{
    private $migrationsColumnName;

    private $migrationsTableName;

    private $destPath;

    /** @var null|OutputWriter */
    private $outputWriter;

    /**
     * @param string $migrationsColumnName
     * @param string $migrationsTableName
     * @param string $destPath
     * @param \Doctrine\DBAL\Migrations\OutputWriter $outputWriter
     */
    public function __construct(
        $migrationsColumnName,
        $migrationsTableName,
        $destPath,
        OutputWriter $outputWriter = null
    ) {
        if (empty($migrationsColumnName)) {
            $this->throwInvalidArgumentException('Migrations column name cannot be empty.');
        }

        if (empty($migrationsTableName)) {
            $this->throwInvalidArgumentException('Migrations table name cannot be empty.');
        }

        if (empty($destPath)) {
            $this->throwInvalidArgumentException('Destination file must be specified.');
        }

        $this->migrationsColumnName = $migrationsColumnName;
        $this->migrationsTableName  = $migrationsTableName;
        $this->destPath             = $destPath;
        $this->outputWriter         = $outputWriter;
    }

    /**
     * @param array $queriesByVersion array Keys are versions and values are arrays of SQL queries (they must be castable to string)
     * @param string $direction
     * @return int|bool
     */
    public function write(array $queriesByVersion, $direction)
    {
        $path   = $this->buildMigrationFilePath();
        $string = $this->buildMigrationFile($queriesByVersion, $direction);

        if ($this->outputWriter) {
            $this->outputWriter->write("\n" . sprintf('Writing migration file to "<info>%s</info>"', $path));
        }

        return file_put_contents($path, $string);
    }

    private function buildMigrationFile(array $queriesByVersion, string $direction) : string
    {
        $string = sprintf("-- Doctrine Migration File Generated on %s\n", date('Y-m-d H:i:s'));

        foreach ($queriesByVersion as $version => $queries) {
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

        return sprintf($query, $this->migrationsTableName, $this->migrationsColumnName, $version);
    }

    private function buildMigrationFilePath() : string
    {
        $path = $this->destPath;

        if (is_dir($path)) {
            $path = realpath($path);
            $path = $path . '/doctrine_migration_' . date('YmdHis') . '.sql';
        }

        return $path;
    }

    protected function throwInvalidArgumentException($message)
    {
        throw new InvalidArgumentException($message);
    }
}
