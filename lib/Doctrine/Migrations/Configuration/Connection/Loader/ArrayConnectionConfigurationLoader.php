<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Connection\Loader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Connection\ConnectionLoaderInterface;
use InvalidArgumentException;
use function file_exists;
use function is_array;

class ArrayConnectionConfigurationLoader implements ConnectionLoaderInterface
{
    /** @var null|string */
    private $filename;

    public function __construct(?string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * Read the input and return a Configuration, returns null if the config
     * is not supported.
     *
     * @throws InvalidArgumentException
     */
    public function chosen() : ?Connection
    {
        if (empty($this->filename)) {
            return null;
        }

        if (! file_exists($this->filename)) {
            return null;
        }

        $params = include $this->filename;

        if (! is_array($params)) {
            throw new InvalidArgumentException(
                'The connection file has to return an array with database configuration parameters.'
            );
        }

        return DriverManager::getConnection($params);
    }
}
