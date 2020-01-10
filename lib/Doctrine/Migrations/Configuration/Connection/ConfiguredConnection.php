<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Connection;

use Doctrine\DBAL\Connection;

class ConfiguredConnection implements ConnectionLoader
{
    /** @var string */
    private $dbConfig;

    public function __construct(string $dbConfig = 'migrations-db.php')
    {
        $this->dbConfig = $dbConfig;
    }

    public function getConnection() : Connection
    {
        $loader = new ConfigurationFile($this->dbConfig);

        return $loader->getConnection();
    }
}
