<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Migrations\Generator\FileBuilder;
use Doctrine\Migrations\Query\Query;
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
    public function __construct(
        private readonly FileBuilder $migrationFileBuilder,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** @param array<string,Query[]> $queriesByVersion */
    public function write(
        string $path,
        string $direction,
        array $queriesByVersion,
        DateTimeInterface|null $now = null,
    ): bool {
        $now ??= new DateTimeImmutable();

        $string = $this->migrationFileBuilder
            ->buildMigrationFile($queriesByVersion, $direction, $now);

        $path = $this->buildMigrationFilePath($path, $now);

        $this->logger->info('Writing migration file to "{path}"', ['path' => $path]);

        return file_put_contents($path, $string) !== false;
    }

    private function buildMigrationFilePath(string $path, DateTimeInterface $now): string
    {
        if (is_dir($path)) {
            $path  = realpath($path);
            $path .= '/doctrine_migration_' . $now->format('YmdHis') . '.sql';
        }

        return $path;
    }
}
