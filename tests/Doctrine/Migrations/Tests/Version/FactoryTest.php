<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Version;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Version\ExecutorInterface;
use Doctrine\Migrations\Version\Factory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class FactoryTest extends TestCase
{
    /** @var ExecutorInterface */
    private $migrationExecutor;

    /** @var Factory */
    private $migrationFactory;


    private $connection;

    private $logger;

    public function testCreateVersion() : void
    {
        $migration = $this->versionFactory->createVersion(
            VersionFactoryTestMigration::class
        );

        self::assertInstanceOf(VersionFactoryTestMigration::class, $migration);
        self::assertSame($this->connection, $migration->getConnection());

        $ref = new \ReflectionProperty(AbstractMigration::class,'logger');
        $ref->setAccessible(true);
        self::assertSame($this->logger,$ref->getValue($migration));

        $ref = new \ReflectionProperty(AbstractMigration::class,'executor');
        $ref->setAccessible(true);
        self::assertSame($this->versionExecutor,$ref->getValue($migration));
    }

    protected function setUp() : void
    {
        $this->connection   = $this->createMock(Connection::class);
        $this->versionExecutor = $this->createMock(ExecutorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->versionFactory = new Factory(
            $this->connection,
            $this->versionExecutor,
            $this->logger
        );
    }
}

class VersionFactoryTestMigration extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
    }

    public function down(Schema $schema) : void
    {
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
