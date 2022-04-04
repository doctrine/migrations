<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Exception\InvalidLoader;

final class ExistingConnection implements ConnectionLoader
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getConnection(?string $name = null): Connection
    {
        if ($name !== null) {
            throw InvalidLoader::noMultipleConnections($this);
        }

        return $this->connection;
    }
}
