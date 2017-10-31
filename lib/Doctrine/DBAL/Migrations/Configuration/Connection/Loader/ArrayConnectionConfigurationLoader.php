<?php

namespace Doctrine\DBAL\Migrations\Configuration\Connection\Loader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Migrations\Configuration\Connection\ConnectionLoaderInterface;

class ArrayConnectionConfigurationLoader implements ConnectionLoaderInterface
{
    private $filename;

    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    /**
     * read the input and return a Configuration, returns `false` if the config
     * is not supported
     * @return Connection|null
     */
    public function chosen()
    {
        if (empty($this->filename)) {
            return null;
        }

        if ( ! file_exists($this->filename)) {
            return null;
        }

        $params = include $this->filename;
        if ( ! is_array($params)) {
            throw new \InvalidArgumentException('The connection file has to return an array with database configuration parameters.');
        }

        return DriverManager::getConnection($params);
    }
}
