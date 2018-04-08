<?php

namespace Doctrine\DBAL\Migrations\Configuration\Connection\Loader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Configuration\Connection\ConnectionLoaderInterface;

final class ConnectionConfigurationChainLoader implements ConnectionLoaderInterface
{
    /** @var  ConnectionLoaderInterface[] */
    private $loaders;

    /**
     * @param ConnectionLoaderInterface[] $loaders
     */
    public function __construct(array $loaders)
    {
        $this->loaders = $loaders;
    }

    /**
     * read the input and return a Configuration, returns `false` if the config
     * is not supported
     * @return Connection|null
     */
    public function chosen()
    {
        foreach ($this->loaders as $loader) {
            $confObj = $loader->chosen();
            if ($confObj !== null) {
                return $confObj;
            }
        }

        return null;
    }
}
