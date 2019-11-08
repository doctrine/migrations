<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Connection\Loader\ArrayConnectionConfigurationLoader;
use Doctrine\Migrations\Configuration\Connection\Loader\ConnectionHelperLoader;
use Doctrine\Migrations\Configuration\Connection\Loader\NoConnectionLoader;
use Doctrine\Migrations\Configuration\Connection\Loader\ShardedConnectionLoader;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * The ConnectionLoader class is responsible for loading the Doctrine\DBAL\Connection instance to use for migrations.
 *
 * @internal
 */
final class ConnectionLoader
{
    public function getConnection(
        ?string $dbConfig,
        ?string $shard,
        HelperSet $helperSet
    ) : Connection {
        // create a chain of connection loaders
        $loader = new NoConnectionLoader();
        $loader = new ConnectionHelperLoader('connection', $loader, $helperSet);
        $loader = new ArrayConnectionConfigurationLoader('migrations-db.php', $loader);
        $loader = new ArrayConnectionConfigurationLoader($dbConfig, $loader);
        $loader = new ShardedConnectionLoader($shard, $loader);

        return $loader->getConnection();
    }
}
