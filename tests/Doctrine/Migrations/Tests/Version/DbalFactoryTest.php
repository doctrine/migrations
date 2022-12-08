<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Version;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\DbalMigrationFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionProperty;

final class DbalFactoryTest extends TestCase
{
    /** @var Connection&MockObject */
    private Connection $connection;
    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;

    private DbalMigrationFactory $versionFactory;

    public function testCreateVersion(): void
    {
        $migration = $this->versionFactory->createVersion(
            VersionFactoryTestMigration::class
        );

        self::assertInstanceOf(VersionFactoryTestMigration::class, $migration);
        self::assertSame($this->connection, $migration->getConnection());

        $ref = new ReflectionProperty(AbstractMigration::class, 'logger');
        $ref->setAccessible(true);
        self::assertSame($this->logger, $ref->getValue($migration));
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->logger     = $this->createMock(LoggerInterface::class);

        $this->versionFactory = new DbalMigrationFactory(
            $this->connection,
            $this->logger
        );
    }
}

class VersionFactoryTestMigration extends AbstractMigration
{
    public function up(Schema $schema): void
    {
    }

    public function down(Schema $schema): void
    {
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
