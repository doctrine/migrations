<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\EntityManager;

use Doctrine\Migrations\Configuration\EntityManager\ManagerRegistryEntityManager;
use Doctrine\Migrations\Tests\Stub\DoctrineRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\AbstractManagerRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EntityManagerRegistryLoaderTest extends TestCase
{
    /** @var EntityManager&MockObject */
    private EntityManager $em1;

    /** @var EntityManager&MockObject */
    private EntityManager $em2;

    private AbstractManagerRegistry $registry;

    public function setUp(): void
    {
        $this->em1      = $this->createMock(EntityManager::class);
        $this->em2      = $this->createMock(EntityManager::class);
        $this->registry = new DoctrineRegistry([], ['foo' => $this->em1, 'bar' => $this->em2]);
    }

    public function testLoadDefaultConnection(): void
    {
        $loader = ManagerRegistryEntityManager::withSimpleDefault($this->registry);

        self::assertSame($this->em1, $loader->getEntityManager());
    }

    public function testLoadAnotherConnection(): void
    {
        $loader = ManagerRegistryEntityManager::withSimpleDefault($this->registry);

        self::assertSame($this->em2, $loader->getEntityManager('bar'));
    }

    public function testUnknownConnection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $loader = ManagerRegistryEntityManager::withSimpleDefault($this->registry);

        $loader->getEntityManager('unknown');
    }
}
