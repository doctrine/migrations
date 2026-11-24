<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\EntityManager;

use Doctrine\DBAL\Platforms\SQLitePlatform;
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
        $em     = self::createStub(EntityManager::class);
        $loader = new ExistingEntityManager($em);

        self::assertSame($em, $loader->getEntityManager());
    }

    public function testNamedConnectionIsNotSupported(): void
    {
        $em     = self::createStub(EntityManager::class);
        $loader = new ExistingEntityManager($em);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only one entity manager is supported by Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager');

        $loader->getEntityManager('foo');
    }

    public function testArrayEntityManagerConfigurationLoader(): void
    {
        $loader = new ConfigurationFile(__DIR__ . '/_files/em-loader.php');
        $em     = $loader->getEntityManager();

        self::assertInstanceOf(SQLitePlatform::class, $em->getConnection()->getDatabasePlatform());
    }

    public function testArrayEntityManagerConfigurationLoaderWithEntityManagerInstance(): void
    {
        $loader = new ConfigurationFile(__DIR__ . '/_files/migrations-em.php');
        $em     = $loader->getEntityManager();

        self::assertInstanceOf(SQLitePlatform::class, $em->getConnection()->getDatabasePlatform());
    }

    public function testArrayEntityManagerConfigurationLoaderInvalid(): void
    {
        $loader = new ConfigurationFile(__DIR__ . '/_files/em-invalid.php');

        $this->expectException(InvalidConfiguration::class);

        $loader->getEntityManager();
    }

    public function testArrayEntityManagerConfigurationLoaderNotFound(): void
    {
        $loader = new ConfigurationFile(__DIR__ . '/_files/not-found.php');

        $this->expectException(FileNotFound::class);

        $loader->getEntityManager();
    }
}
