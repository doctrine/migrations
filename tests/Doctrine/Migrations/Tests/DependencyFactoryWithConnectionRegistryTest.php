<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ConnectionRegistryConnection;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tests\Stub\DoctrineRegistry;
use Doctrine\Persistence\AbstractManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;

final class DependencyFactoryWithConnectionRegistryTest extends MigrationTestCase
{
    /** @var Connection&MockObject */
    private Connection $connection1;

    /** @var Connection&MockObject */
    private Connection $connection2;

    private Configuration $configuration;

    private AbstractManagerRegistry $registry;

    private DependencyFactory $di;

    public function setUp(): void
    {
        $this->configuration = new Configuration();

        $this->connection1 = $this->createMock(Connection::class);
        $this->connection2 = $this->createMock(Connection::class);
        $this->registry    = new DoctrineRegistry(['foo' => $this->connection1, 'bar' => $this->connection2], []);

        $this->di = DependencyFactory::fromConnection(
            new ExistingConfiguration($this->configuration),
            ConnectionRegistryConnection::withSimpleDefault($this->registry)
        );
    }

    public function testGetConnectionFromRegistry(): void
    {
        self::assertSame($this->connection1, $this->di->getConnection());
    }

    public function testGetAlternativeConnectionFromRegistry(): void
    {
        $this->configuration->setConnectionName('bar');
        self::assertSame($this->connection2, $this->di->getConnection());
    }
}
