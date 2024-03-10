<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Connection\Exception\FileNotFound;
use Doctrine\Migrations\Configuration\Connection\Exception\InvalidConfiguration;
use InvalidArgumentException;

use function file_exists;
use function is_array;

/**
 * This class will return a Connection instance, loaded from a configuration file provided as argument.
 */
final class ConfigurationFile implements ConnectionLoader
{
    public function __construct(private readonly string $filename)
    {
    }

    public function getConnection(string|null $name = null): Connection
    {
        if ($name !== null) {
            throw new InvalidArgumentException('Only one connection is supported');
        }

        if (! file_exists($this->filename)) {
            throw FileNotFound::new($this->filename);
        }

        $params = include $this->filename;

        if ($params instanceof Connection) {
            return $params;
        }

        if ($params instanceof ConnectionLoader) {
            return $params->getConnection();
        }

        if (is_array($params)) {
            return DriverManager::getConnection($params);
        }

        throw InvalidConfiguration::invalidArrayConfiguration();
    }
}
