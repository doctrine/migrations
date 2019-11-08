<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Connection\Loader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Sharding\PoolingShardConnection;
use Doctrine\Migrations\Configuration\Connection\ConnectionLoaderInterface;
use Doctrine\Migrations\Configuration\Connection\Loader\Exception\InvalidConfiguration;

/**
 * The ConnectionHelperLoader is responsible for loading a Doctrine\DBAL\Connection from a Symfony Console HelperSet.
 *
 * @internal
 */
final class ShardedConnectionLoader implements ConnectionLoaderInterface
{
    /** @var string|null */
    private $shard;

    /** @var ConnectionLoaderInterface */
    private $fallback;

    public function __construct(?string $shard, ConnectionLoaderInterface $fallback)
    {
        $this->shard    = $shard;
        $this->fallback = $fallback;
    }

    /**
     * Read the input and return a Configuration, returns null if the config
     * is not supported.
     */
    public function getConnection() : Connection
    {
        $connection = $this->fallback->getConnection();

        if ($this->shard === null) {
            return $connection;
        }

        if (! $connection instanceof PoolingShardConnection) {
            throw InvalidConfiguration::notShardedConnection();
        }

        $connection->connect($this->shard);

        return $connection;
    }
}
