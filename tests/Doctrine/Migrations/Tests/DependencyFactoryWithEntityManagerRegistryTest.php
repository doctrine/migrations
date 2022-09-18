<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\EntityManager\ManagerRegistryEntityManager;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tests\Stub\DoctrineRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\AbstractManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;

final class DependencyFactoryWithEntityManagerRegistryTest extends MigrationTestCase
{
    /** @var Connection&MockObject */
    private Connection $connection1;

    /** @var Connection&MockObject */
    private Connection $connection2;

    /** @var EntityManager&MockObject */
    private EntityManager $em1;

    /** @var EntityManager&MockObject */
    private EntityManager $em2;

    private Configuration $configuration;

    private AbstractManagerRegistry $registry;

    private DependencyFactory $di;

    public function setUp(): void
    {
        $this->configuration = new Configuration();

        $this->connection1 = $this->createMock(Connection::class);
        $this->connection2 = $this->createMock(Connection::class);

        $this->em1 = $this->createMock(EntityManager::class);
        $this->em2 = $this->createMock(EntityManager::class);

        $this->em1
            ->method('getConnection')
            ->willReturn($this->connection1);

        $this->em2
            ->method('getConnection')
            ->willReturn($this->connection2);

        $this->registry = new DoctrineRegistry([], ['foo' => $this->em1, 'bar' => $this->em2]);

        $this->di = DependencyFactory::fromEntityManager(
            new ExistingConfiguration($this->configuration),
            ManagerRegistryEntityManager::withSimpleDefault($this->registry)
        );
    }

    public function testGetEntityManagerFromRegistry(): void
    {
        self::assertSame($this->em1, $this->di->getEntityManager());
    }

    public function testGetAlternativeEntityManagerFromRegistry(): void
    {
        $this->configuration->setEntityManagerName('bar');
        self::assertSame($this->em2, $this->di->getEntityManager());
    }

    public function testGetConnectionFromRegistry(): void
    {
        self::assertSame($this->connection1, $this->di->getConnection());
    }

    public function testGetAlternativeConnectionFromRegistry(): void
    {
        $this->configuration->setEntityManagerName('bar');
        self::assertSame($this->connection2, $this->di->getConnection());
    }
}
