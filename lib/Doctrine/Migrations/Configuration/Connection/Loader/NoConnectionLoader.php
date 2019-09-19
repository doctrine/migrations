<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Connection\Loader;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Connection\ConnectionLoaderInterface;
use Doctrine\Migrations\Configuration\Connection\Loader\Exception\InvalidConfiguration;
use Doctrine\Migrations\Tools\Console\Exception\ConnectionNotSpecified;

/**
 * The ConnectionConfigurationChainLoader class is responsible for loading a Doctrine\DBAL\Connection from an array of
 * loaders. The first one to return a Connection is used.
 *
 * @internal
 */
final class NoConnectionLoader implements ConnectionLoaderInterface
{
    public function getConnection() : Connection
    {
        throw ConnectionNotSpecified::new();
    }
}
