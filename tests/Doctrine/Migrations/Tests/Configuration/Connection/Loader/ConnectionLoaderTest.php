<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Connection\Loader;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\Migrations\Configuration\Connection\Loader\ArrayConnectionConfigurationLoader;
use Doctrine\Migrations\Configuration\Connection\Loader\Exception\ConnectionNotSpecified;
use Doctrine\Migrations\Configuration\Connection\Loader\Exception\InvalidConfiguration;
use Doctrine\Migrations\Configuration\Connection\Loader\NoConnectionLoader;
use PHPUnit\Framework\TestCase;

final class ConnectionLoaderTest extends TestCase
{
    public function testNoConnectionLoader() : void
    {
        $this->expectException(ConnectionNotSpecified::class);
        $loader = new NoConnectionLoader();
        $loader->getConnection();
    }

    public function testArrayConnectionConfigurationLoader() : void
    {
        $loader = new ArrayConnectionConfigurationLoader(__DIR__ . '/sqlite-connection.php', new NoConnectionLoader());
        $conn   = $loader->getConnection();

        self::assertInstanceOf(SqlitePlatform::class, $conn->getDatabasePlatform());
    }

    public function testArrayConnectionConfigurationLoaderInvalid() : void
    {
        $this->expectException(InvalidConfiguration::class);
        $loader = new ArrayConnectionConfigurationLoader(__DIR__ . '/sqlite-connection-invalid.php', new NoConnectionLoader());
        $loader->getConnection();
    }

    public function testArrayConnectionConfigurationLoaderNotFound() : void
    {
        $this->expectException(ConnectionNotSpecified::class);
        $loader = new ArrayConnectionConfigurationLoader(__DIR__ . '/not-found.php', new NoConnectionLoader());
        $loader->getConnection();
    }

    public function testArrayConnectionConfigurationLoaderNoFile() : void
    {
        $this->expectException(ConnectionNotSpecified::class);
        $loader = new ArrayConnectionConfigurationLoader(null, new NoConnectionLoader());
        $loader->getConnection();
    }
}
