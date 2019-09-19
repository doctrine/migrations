<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Connection\Loader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\Loader\ArrayConnectionConfigurationLoader;
use Doctrine\Migrations\Configuration\Connection\Loader\ConnectionConfigurationLoader;
use Doctrine\Migrations\Configuration\Connection\Loader\ConnectionHelperLoader;
use Doctrine\Migrations\Configuration\Connection\Loader\Exception\InvalidConfiguration;
use Doctrine\Migrations\Configuration\Connection\Loader\NoConnectionLoader;
use Doctrine\Migrations\Tools\Console\Exception\ConnectionNotSpecified;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Helper\HelperSet;

final class ConnectionLoaderTest extends TestCase
{
    public function testNoConnectionLoader()
    {
        $this->expectException(ConnectionNotSpecified::class);
        $loader = new NoConnectionLoader();
        $loader->getConnection();
    }

    public function testArrayConnectionConfigurationLoader()
    {
        $loader = new ArrayConnectionConfigurationLoader(__DIR__ . '/sqlite-connection.php', new NoConnectionLoader());
        $conn = $loader->getConnection();

        self::assertInstanceOf(Connection::class, $conn);
    }

    public function testArrayConnectionConfigurationLoaderInvalid()
    {
        $this->expectException(InvalidConfiguration::class);
        $loader = new ArrayConnectionConfigurationLoader(__DIR__ . '/sqlite-connection-invalid.php', new NoConnectionLoader());
        $loader->getConnection();
    }

    public function testArrayConnectionConfigurationLoaderNotFound()
    {
        $this->expectException(ConnectionNotSpecified::class);
        $loader = new ArrayConnectionConfigurationLoader(__DIR__ . '/not-found.php', new NoConnectionLoader());
        $loader->getConnection();
    }

    public function testArrayConnectionConfigurationLoaderNoFile()
    {
        $this->expectException(ConnectionNotSpecified::class);
        $loader = new ArrayConnectionConfigurationLoader(null, new NoConnectionLoader());
        $loader->getConnection();
    }

    public function testConnectionHelperLoader()
    {
        $connection = $this->createMock(Connection::class);

        $helper = $this->createMock(ConnectionHelper::class);
        $helper->expects($this->once())->method('getConnection')->willReturn($connection);

        $helperSet = new HelperSet();
        $helperSet->set($helper, 'connection');
        $loader = new ConnectionHelperLoader($helperSet, 'connection', new NoConnectionLoader());
        $conn = $loader->getConnection();

        self::assertInstanceOf(Connection::class, $conn);
    }

    public function testConnectionHelperLoaderNoHelper()
    {
        $this->expectException(ConnectionNotSpecified::class);
        $helperSet = new HelperSet();
        $loader = new ConnectionHelperLoader($helperSet, 'connection', new NoConnectionLoader());
        $loader->getConnection();
    }
}
