<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Connection\Loader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Connection\ConnectionLoaderInterface;
use Doctrine\Migrations\Configuration\Connection\Loader\Exception\InvalidConfiguration;
use function file_exists;
use function is_array;

/**
 * The ArrayConnectionConfigurationLoader class is responsible for loading a Doctrine\DBAL\Connection from a PHP file
 * that returns an array of connection information which is used to instantiate a connection with DriverManager::getConnection()
 *
 * @internal
 */
final class ArrayConnectionConfigurationLoader implements ConnectionLoaderInterface
{
    /** @var string|null */
    private $filename;

    /** @var ConnectionLoaderInterface */
    private $fallback;

    public function __construct(?string $filename, ConnectionLoaderInterface $fallback)
    {
        $this->filename = $filename;
        $this->fallback = $fallback;
    }

    /**
     * Read the input and return a Configuration, returns null if the config
     * is not supported.
     *
     * @throws InvalidConfiguration
     */
    public function getConnection() : Connection
    {
        if ($this->filename === null) {
            return $this->fallback->getConnection();
        }

        if (! file_exists($this->filename)) {
            return $this->fallback->getConnection();
        }

        $params = include $this->filename;

        if (! is_array($params)) {
            throw InvalidConfiguration::invalidArrayConfiguration();
        }

        return DriverManager::getConnection($params);
    }
}
