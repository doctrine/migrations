<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Connection\Loader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\Migrations\Configuration\Connection\Loader\ArrayConnectionConfigurationLoader;
use Doctrine\Migrations\Configuration\Connection\Loader\ConnectionHelperLoader;
use Doctrine\Migrations\Configuration\Connection\Loader\Exception\InvalidConfiguration;
use Doctrine\Migrations\Configuration\Connection\Loader\NoConnectionLoader;
use Doctrine\Migrations\Tools\Console\Exception\ConnectionNotSpecified;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;

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

    public function testConnectionHelperLoader() : void
    {
        $connection = $this->createMock(Connection::class);

        $helper = $this->createMock(ConnectionHelper::class);
        $helper->expects(self::once())->method('getConnection')->willReturn($connection);

        $helperSet = new HelperSet();
        $helperSet->set($helper, 'connection');
        $loader = new ConnectionHelperLoader('connection', new NoConnectionLoader(), $helperSet);
        $conn   = $loader->getConnection();

        self::assertSame($connection, $conn);
    }

    public function testConnectionHelperLoaderNoHelper() : void
    {
        $this->expectException(ConnectionNotSpecified::class);
        $helperSet = new HelperSet();
        $loader    = new ConnectionHelperLoader('connection', new NoConnectionLoader(), $helperSet);
        $loader->getConnection();
    }
}
