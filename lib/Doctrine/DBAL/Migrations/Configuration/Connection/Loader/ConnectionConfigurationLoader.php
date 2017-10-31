<?php

namespace Doctrine\DBAL\Migrations\Configuration\Connection\Loader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Configuration\Connection\ConnectionLoaderInterface;

class ConnectionConfigurationLoader implements ConnectionLoaderInterface
{
    /** @var Configuration */
    private $configuration;

    public function __construct(Configuration $configuration = null)
    {
        if ($configuration !== null) {
            $this->configuration = $configuration;
        }
    }

    /**
     * read the input and return a Configuration, returns `false` if the config
     * is not supported
     * @return Connection|null
     */
    public function chosen()
    {
        if ($this->configuration) {
            return $this->configuration->getConnection();
        }

        return null;
    }
}
