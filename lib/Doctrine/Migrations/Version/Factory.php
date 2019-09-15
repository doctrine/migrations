<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\LoggerInterface;

/**
 * The Factory class is responsible for creating instances of the Version class for a version number
 * and a migration class name.
 *
 * @var internal
 */
class Factory
{
    /** @var Connection */
    private $connection;

    /** @var ExecutorInterface */
    private $versionExecutor;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Connection $connection, ExecutorInterface $versionExecutor, LoggerInterface $logger)
    {
        $this->connection      = $connection;
        $this->versionExecutor = $versionExecutor;
        $this->logger          = $logger;
    }

    public function createVersion(string $migrationClassName) : AbstractMigration
    {
        return new $migrationClassName(
            $this->versionExecutor,
            $this->connection,
            $this->logger
        );
    }
}
