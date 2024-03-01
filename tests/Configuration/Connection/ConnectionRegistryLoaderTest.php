<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Connection\ConnectionRegistryConnection;
use Doctrine\Migrations\Tests\Stub\DoctrineRegistry;
use Doctrine\Persistence\AbstractManagerRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ConnectionRegistryLoaderTest extends TestCase
{
    /** @var Connection&MockObject */
    private Connection $connection1;

    /** @var Connection&MockObject */
    private Connection $connection2;

    private AbstractManagerRegistry $registry;

    public function setUp(): void
    {
        $this->connection1 = $this->createMock(Connection::class);
        $this->connection2 = $this->createMock(Connection::class);
        $this->registry    = new DoctrineRegistry(['foo' => $this->connection1, 'bar' => $this->connection2], []);
    }

    public function testLoadDefaultConnection(): void
    {
        $loader = ConnectionRegistryConnection::withSimpleDefault($this->registry);

        self::assertSame($this->connection1, $loader->getConnection());
    }

    public function testLoadAnotherConnection(): void
    {
        $loader = ConnectionRegistryConnection::withSimpleDefault($this->registry);

        self::assertSame($this->connection2, $loader->getConnection('bar'));
    }

    public function testUnknownConnection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $loader = ConnectionRegistryConnection::withSimpleDefault($this->registry);

        $loader->getConnection('unknown');
    }
}
