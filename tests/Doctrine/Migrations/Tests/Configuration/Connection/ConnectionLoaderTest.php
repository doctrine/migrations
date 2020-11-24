<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\Migrations\Configuration\Connection\ConfigurationFile;
use Doctrine\Migrations\Configuration\Connection\Exception\FileNotFound;
use Doctrine\Migrations\Configuration\Connection\Exception\InvalidConfiguration;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ConnectionLoaderTest extends TestCase
{
    public function testExistingConnectionLoader(): void
    {
        $conn   = $this->createMock(Connection::class);
        $loader = new ExistingConnection($conn);

        self::assertSame($conn, $loader->getConnection());
    }

    public function testNamedConnectionIsNotSupported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only one connection is supported by Doctrine\Migrations\Configuration\Connection\ExistingConnection');

        $conn   = $this->createMock(Connection::class);
        $loader = new ExistingConnection($conn);

        self::assertSame($conn, $loader->getConnection('foo'));
    }

    public function testArrayConnectionConfigurationLoader(): void
    {
        $loader = new ConfigurationFile(__DIR__ . '/_files/sqlite-connection.php');
        $conn   = $loader->getConnection();

        self::assertInstanceOf(SqlitePlatform::class, $conn->getDatabasePlatform());
    }

    public function testArrayConnectionConfigurationLoaderWithConnectionInstance(): void
    {
        $loader = new ConfigurationFile(__DIR__ . '/_files/sqlite-connection-instance.php');
        $conn   = $loader->getConnection();

        self::assertInstanceOf(SqlitePlatform::class, $conn->getDatabasePlatform());
    }

    public function testArrayConnectionConfigurationLoaderInvalid(): void
    {
        $this->expectException(InvalidConfiguration::class);
        $loader = new ConfigurationFile(__DIR__ . '/_files/sqlite-connection-invalid.php');
        $loader->getConnection();
    }

    public function testArrayConnectionConfigurationLoaderNotFound(): void
    {
        $this->expectException(FileNotFound::class);
        $loader = new ConfigurationFile(__DIR__ . '/_files/not-found.php');
        $loader->getConnection();
    }
}
