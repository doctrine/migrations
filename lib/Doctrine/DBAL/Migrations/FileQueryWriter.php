<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations;

/**
 * @since  1.6.0
 * @author Luís Cobucci <lcobucci@gmail.com>
 */
final class FileQueryWriter implements QueryWriter
{
    /**
     * @var string
     */
    private $columnName;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var null|OutputWriter
     */
    private $outputWriter;

    public function __construct(string $columnName, string $tableName, ?OutputWriter $outputWriter)
    {
        $this->columnName   = $columnName;
        $this->tableName    = $tableName;
        $this->outputWriter = $outputWriter;
    }

    /**
     * TODO: move SqlFileWriter's behaviour to this class - and kill it with fire (on the next major release)
     * @param string $path
     * @param string $direction
     * @param array $queriesByVersion
     * @return bool
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
