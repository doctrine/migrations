<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations;

final class FileQueryWriter implements QueryWriter
{
    /** @var string */
    private $columnName;

    /** @var string */
    private $tableName;

    /** @var null|OutputWriter */
    private $outputWriter;

    public function __construct(string $columnName, string $tableName, ?OutputWriter $outputWriter)
    {
        $this->columnName   = $columnName;
        $this->tableName    = $tableName;
        $this->outputWriter = $outputWriter;
    }

    /**
     * TODO: move SqlFileWriter's behaviour to this class - and kill it with fire (on the next major release)
     * @param string[][] $queriesByVersion
     */
    public function write(string $path, string $direction, array $queriesByVersion) : bool
    {
        $writer = new SqlFileWriter(
            $this->columnName,
            $this->tableName,
            $path,
            $this->outputWriter
        );

        // SqlFileWriter#write() returns `bool|int` but all clients expect it to be `bool` only
        return (bool) $writer->write($queriesByVersion, $direction);
    }
}
