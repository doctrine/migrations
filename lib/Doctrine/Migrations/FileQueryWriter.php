<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Migrations\Generator\FileBuilderInterface;
use Psr\Log\LoggerInterface;
use function file_put_contents;
use function is_dir;
use function realpath;

/**
 * The FileQueryWriter class is responsible for writing migration SQL queries to a file on disk.
 *
 * @internal
 */
final class FileQueryWriter implements QueryWriter
{
    /** @var FileBuilderInterface */
    private $migrationFileBuilder;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        FileBuilderInterface $migrationFileBuilder,
        LoggerInterface $logger
    ) {
        $this->migrationFileBuilder = $migrationFileBuilder;
        $this->logger               = $logger;
    }

    /**
     * @param mixed[] $queriesByVersion
     */
    public function write(
        string $path,
        string $direction,
        array $queriesByVersion,
        ?DateTimeInterface $now = null
    ) : bool {
        $now = $now ?? new DateTimeImmutable();

        $string = $this->migrationFileBuilder
            ->buildMigrationFile($queriesByVersion, $direction, $now);

        $path = $this->buildMigrationFilePath($path, $now);

        $this->logger->info('Writing migration file to "{path}"', ['path' => $path]);

        return file_put_contents($path, $string) !== false;
    }

    private function buildMigrationFilePath(string $path, DateTimeInterface $now) : string
    {
        if (is_dir($path)) {
            $path  = realpath($path);
            $path .= '/doctrine_migration_' . $now->format('YmdHis') . '.sql';
        }

        return $path;
    }
}
