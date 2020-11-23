<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Connection\Exception\FileNotFound;
use Doctrine\Migrations\Configuration\Connection\Exception\InvalidConfiguration;

use function file_exists;
use function is_array;

/**
 * This class will return a Connection instance, loaded from a configuration file provided as argument.
 */
final class ConfigurationFile implements ConnectionLoader
{
    /** @var string */
    private $filename;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function getConnection(): Connection
    {
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
