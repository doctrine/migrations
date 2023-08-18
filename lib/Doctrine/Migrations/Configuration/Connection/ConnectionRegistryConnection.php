<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Connection\Exception\InvalidConfiguration;
use Doctrine\Persistence\ConnectionRegistry;

final class ConnectionRegistryConnection implements ConnectionLoader
{
    private ConnectionRegistry $registry;

    private string|null $defaultConnectionName = null;

    public static function withSimpleDefault(ConnectionRegistry $registry, string|null $connectionName = null): self
    {
        $that                        = new self();
        $that->registry              = $registry;
        $that->defaultConnectionName = $connectionName;

        return $that;
    }

    private function __construct()
    {
    }

    public function getConnection(string|null $name = null): Connection
    {
        $connection = $this->registry->getConnection($name ?? $this->defaultConnectionName);
        if (! $connection instanceof Connection) {
            throw InvalidConfiguration::invalidConnectionType($connection);
        }

        return $connection;
    }
}
