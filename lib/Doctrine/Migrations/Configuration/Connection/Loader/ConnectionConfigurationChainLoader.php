<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Connection\Loader;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Connection\ConnectionLoaderInterface;

/**
 * @internal
 */
final class ConnectionConfigurationChainLoader implements ConnectionLoaderInterface
{
    /** @var ConnectionLoaderInterface[] */
    private $loaders = [];

    /**
     * @param ConnectionLoaderInterface[] $loaders
     */
    public function __construct(array $loaders)
    {
        $this->loaders = $loaders;
    }

    /**
     * Read the input and return a Configuration, returns null if the config
     * is not supported.
     *
     * @throws InvalidArgumentException
     */
    public function chosen() : ?Connection
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
