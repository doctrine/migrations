<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Connection\Exception\ConnectionNotSpecified;

/**
 * The ConnectionLoader defines the interface used to load the Doctrine\DBAL\Connection instance to use
 * for migrations.
 *
 * @internal
 */
interface ConnectionLoader
{
    /**
     * Read the input and return a Connection, returns null if the config
     * is not supported.
     *
     * @throws ConnectionNotSpecified
     */
    public function getConnection(): Connection;
}
