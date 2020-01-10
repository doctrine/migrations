<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\EntityManager;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\Migrations\Configuration\EntityManager\ConfigurationFile;
use Doctrine\Migrations\Configuration\EntityManager\ConfiguredEntityManager;
use Doctrine\Migrations\Configuration\EntityManager\Exception\FileNotFound;
use Doctrine\Migrations\Configuration\EntityManager\Exception\InvalidConfiguration;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use function chdir;
use function getcwd;

final class EntityManagerTest extends TestCase
{
    public function testExistingEntityManagerLoader() : void
    {
        $em     = $this->createMock(EntityManager::class);
        $loader = new ExistingEntityManager($em);

        self::assertSame($em, $loader->getEntityManager());
    }

    public function testArrayEntityManagerConfigurationLoader() : void
    {
        $loader = new ConfigurationFile(__DIR__ . '/_files/em-loader.php');
        $em     = $loader->getEntityManager();

        self::assertSame('Foo', $em->getConfiguration()->getProxyNamespace());
        self::assertInstanceOf(SqlitePlatform::class, $em->getConnection()->getDatabasePlatform());
    }

    public function testArrayEntityManagerConfigurationLoaderWithEntityManagerInstance() : void
    {
        $loader = new ConfigurationFile(__DIR__ . '/_files/migrations-em.php');
        $em     = $loader->getEntityManager();

        self::assertSame('Foo', $em->getConfiguration()->getProxyNamespace());
        self::assertInstanceOf(SqlitePlatform::class, $em->getConnection()->getDatabasePlatform());
    }

    public function testArrayEntityManagerConfigurationLoaderInvalid() : void
    {
        $this->expectException(InvalidConfiguration::class);
        $loader = new ConfigurationFile(__DIR__ . '/_files/em-invalid.php');
        $loader->getEntityManager();
    }

    public function testArrayEntityManagerConfigurationLoaderNotFound() : void
    {
        $this->expectException(FileNotFound::class);
        $loader = new ConfigurationFile(__DIR__ . '/_files/not-found.php');
        $loader->getEntityManager();
    }

    public function testGetEntityManagerFromLoader() : void
    {
        $dir = getcwd() ?: '.';
        chdir(__DIR__);
        $loader = new ConfiguredEntityManager('_files/em-loader.php');
        try {
            $em = $loader->getEntityManager();
            self::assertSame('Foo', $em->getConfiguration()->getProxyNamespace());
            self::assertInstanceOf(SqlitePlatform::class, $em->getConnection()->getDatabasePlatform());
        } finally {
            chdir($dir);
        }
    }

    public function testGetEntityManagerNotFound() : void
    {
        $this->expectException(FileNotFound::class);

        $dir = getcwd()?: '.';
        chdir(__DIR__);
        $loader = new ConfiguredEntityManager(__DIR__ . '/_files/wrong.php');
        try {
            $loader->getEntityManager();
        } finally {
            chdir($dir);
        }
    }

    public function testGetEntityManager() : void
    {
        $dir = getcwd()?: '.';
        chdir(__DIR__ . '/_files');
        $loader = new ConfiguredEntityManager();
        try {
            $em = $loader->getEntityManager();
            self::assertSame('Foo', $em->getConfiguration()->getProxyNamespace());
            self::assertInstanceOf(SqlitePlatform::class, $em->getConnection()->getDatabasePlatform());
        } finally {
            chdir($dir);
        }
    }
}
