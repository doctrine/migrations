<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\EntityManager;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\Migrations\Configuration\EntityManager\ConfigurationFile;
use Doctrine\Migrations\Configuration\EntityManager\Exception\FileNotFound;
use Doctrine\Migrations\Configuration\EntityManager\Exception\InvalidConfiguration;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EntityManagerTest extends TestCase
{
    public function testExistingEntityManagerLoader(): void
    {
        $em     = $this->createMock(EntityManager::class);
        $loader = new ExistingEntityManager($em);

        self::assertSame($em, $loader->getEntityManager());
    }

    public function testNamedConnectionIsNotSupported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only one entity manager is supported by Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager');

        $em     = $this->createMock(EntityManager::class);
        $loader = new ExistingEntityManager($em);

        self::assertSame($em, $loader->getEntityManager('foo'));
    }

    public function testArrayEntityManagerConfigurationLoader(): void
    {
        $loader = new ConfigurationFile(__DIR__ . '/_files/em-loader.php');
        $em     = $loader->getEntityManager();

        self::assertInstanceOf(SqlitePlatform::class, $em->getConnection()->getDatabasePlatform());
    }

    public function testArrayEntityManagerConfigurationLoaderWithEntityManagerInstance(): void
    {
        $loader = new ConfigurationFile(__DIR__ . '/_files/migrations-em.php');
        $em     = $loader->getEntityManager();

        self::assertInstanceOf(SqlitePlatform::class, $em->getConnection()->getDatabasePlatform());
    }

    public function testArrayEntityManagerConfigurationLoaderInvalid(): void
    {
        $this->expectException(InvalidConfiguration::class);
        $loader = new ConfigurationFile(__DIR__ . '/_files/em-invalid.php');
        $loader->getEntityManager();
    }

    public function testArrayEntityManagerConfigurationLoaderNotFound(): void
    {
        $this->expectException(FileNotFound::class);
        $loader = new ConfigurationFile(__DIR__ . '/_files/not-found.php');
        $loader->getEntityManager();
    }
}
