<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Configuration\Connection\Loader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Configuration\Connection\ConnectionLoaderInterface;

class ConnectionConfigurationLoader implements ConnectionLoaderInterface
{
    /** @var null|Configuration */
    private $configuration;

    public function __construct(?Configuration $configuration = null)
    {
        $this->configuration = $configuration;
    }

    /**
     * Read the input and return a Configuration, returns null if the config
     * is not supported.
     */
    public function chosen() : ?Connection
    {
        if ($this->configuration) {
            return $this->configuration->getConnection();
        }

        return null;
    }
}
