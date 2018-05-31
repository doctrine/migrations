<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Version\Version;
use RuntimeException;
use function count;
use function current;
use function sprintf;

/**
 * @internal
 */
class Rollup
{
    /** @var Configuration */
    private $configuration;

    /** @var Connection */
    private $connection;

    /** @var MigrationRepository */
    private $migrationRepository;

    public function __construct(
        Configuration $configuration,
        Connection $connection,
        MigrationRepository $migrationRepository
    ) {
        $this->configuration       = $configuration;
        $this->connection          = $connection;
        $this->migrationRepository = $migrationRepository;
    }

    public function rollup() : Version
    {
        $versions = $this->migrationRepository->getVersions();

        if (count($versions) === 0) {
            throw new RuntimeException('No migrations found.');
        }

        if (count($versions) > 1) {
            throw new RuntimeException('Too many migrations.');
        }

        $sql = sprintf(
            'DELETE FROM %s',
            $this->configuration->getMigrationsTableName()
        );

        $this->connection->executeQuery($sql);

        $version = current($versions);

        $version->markMigrated();

        return $version;
    }
}
