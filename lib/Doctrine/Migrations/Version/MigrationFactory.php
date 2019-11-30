<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\LoggerInterface;

/**
 * The MigrationFactory class is responsible for creating instances of the Version class for a version number
 * and a migration class name.
 *
 * @var internal
 */
/*final*/ class MigrationFactory
{
    /** @var Connection */
    private $connection;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger     = $logger;
    }

    public function createVersion(string $migrationClassName) : AbstractMigration
    {
        return new $migrationClassName(
            $this->connection,
            $this->logger
        );
    }
}
