<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Connection\Loader;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\Loader\ConnectionConfigurationLoader;
use PHPUnit\Framework\TestCase;

final class ConnectionConfigurationLoaderTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    public function testChosenReturnsNull() : void
    {
        $connectionConfigurationLoader = new ConnectionConfigurationLoader();

        self::assertNull($connectionConfigurationLoader->chosen());
    }

    public function testChosenReturnsConfigurationConnection() : void
    {
        $connection = $this->createMock(Connection::class);

        $this->configuration->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        self::assertSame($connection, $this->connectionConfigurationLoader->chosen());
    }

    protected function setUp() : void
    {
        $this->configuration = $this->createMock(Configuration::class);

        $this->connectionConfigurationLoader = new ConnectionConfigurationLoader($this->configuration);
    }
}
