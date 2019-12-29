<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Connection\Loader\ArrayConnectionConfigurationLoader;
use Doctrine\Migrations\Configuration\Connection\Loader\NoConnectionLoader;

class ConfiguredConnectionLoader implements ConnectionLoader
{
    /** @var string|null */
    private $dbConfig;

    public function __construct(?string $dbConfig)
    {
        $this->dbConfig = $dbConfig;
    }

    public function getConnection() : Connection
    {
        $loader = new ArrayConnectionConfigurationLoader(
            $this->dbConfig ?: 'migrations-db.php',
            new NoConnectionLoader()
        );

        return $loader->getConnection();
    }
}
