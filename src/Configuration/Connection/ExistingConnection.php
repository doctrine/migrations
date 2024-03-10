<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Exception\InvalidLoader;

final class ExistingConnection implements ConnectionLoader
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function getConnection(string|null $name = null): Connection
    {
        if ($name !== null) {
            throw InvalidLoader::noMultipleConnections($this);
        }

        return $this->connection;
    }
}
