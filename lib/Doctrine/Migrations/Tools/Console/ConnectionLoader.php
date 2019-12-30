<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Connection\Loader\ArrayConnectionConfigurationLoader;
use Doctrine\Migrations\Configuration\Connection\Loader\ConnectionHelperLoader;
use Doctrine\Migrations\Configuration\Connection\Loader\NoConnectionLoader;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * The ConnectionLoader class is responsible for loading the Doctrine\DBAL\Connection instance to use for migrations.
 *
 * @internal
 */
class ConnectionLoader
{
    public function getConnection(
        ?string $dbConfig,
        HelperSet $helperSet
    ) : Connection {
        $loader = new ArrayConnectionConfigurationLoader(
            $dbConfig,
            new ArrayConnectionConfigurationLoader(
                'migrations-db.php',
                new ConnectionHelperLoader('connection', new NoConnectionLoader(), $helperSet)
            )
        );

        return $loader->getConnection();
    }
}
