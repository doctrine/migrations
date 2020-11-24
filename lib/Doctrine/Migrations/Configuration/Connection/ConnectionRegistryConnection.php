<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Connection\Exception\InvalidConfiguration;
use Doctrine\Persistence\ConnectionRegistry;

final class ConnectionRegistryConnection implements ConnectionLoader
{
    /** @var ConnectionRegistry */
    private $registry;

    /** @var string|null */
    private $defaultConnectionName;

    public static function withSimpleDefault(ConnectionRegistry $registry, ?string $connectionName = null): self
    {
        $that                        = new self();
        $that->registry              = $registry;
        $that->defaultConnectionName = $connectionName;

        return $that;
    }

    private function __construct()
    {
    }

    public function getConnection(?string $name = null): Connection
    {
        $connection = $this->registry->getConnection($name ?? $this->defaultConnectionName);
        if (! $connection instanceof Connection) {
            throw InvalidConfiguration::invalidConnectionType($connection);
        }

        return $connection;
    }
}
