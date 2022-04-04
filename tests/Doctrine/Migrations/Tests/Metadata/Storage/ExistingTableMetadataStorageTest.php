<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Metadata\Storage;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\FilesystemMigrationsRepository;
use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Tests\Helper;
use Doctrine\Migrations\Version\AlphabeticalComparator;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;

use function sprintf;

class ExistingTableMetadataStorageTest extends TestCase
{
    private Connection $connection;

    private TableMetadataStorage $storage;

    private TableMetadataStorageConfiguration $config;

    /** @var AbstractSchemaManager<AbstractPlatform> */
    private AbstractSchemaManager $schemaManager;

    private MigrationsRepository $migrationRepository;

    private function getSqliteConnection(): Connection
    {
        $params = ['driver' => 'pdo_sqlite', 'memory' => true];

        return DriverManager::getConnection($params);
    }

    public function setUp(): void
    {
        $this->connection    = $this->getSqliteConnection();
        $this->schemaManager = $this->connection->createSchemaManager();

        $migration                 = $this->createMock(AbstractMigration::class);
        $versionFactory            = $this->createMock(MigrationFactory::class);
        $this->migrationRepository = new FilesystemMigrationsRepository(
            [],
            [],
            new RecursiveRegexFinder('#.*\\.php$#i'),
            $versionFactory
        );
        Helper::registerMigrationInstance($this->migrationRepository, new Version('Foo\\5678'), $migration);

        $this->config = new TableMetadataStorageConfiguration();

        $this->storage = new TableMetadataStorage(
            $this->connection,
            new AlphabeticalComparator(),
            $this->config,
            $this->migrationRepository
        );

        // create partial table
        $table = new Table($this->config->getTableName());
        $table->addColumn($this->config->getVersionColumnName(), 'string', ['notnull' => true, 'length' => 24]);
        $table->setPrimaryKey([$this->config->getVersionColumnName()]);
        $this->schemaManager->createTable($table);
    }

    public function testPrimaryReadReplicaConnectionGetsConnected(): void
    {
        $connection = $this->createMock(PrimaryReadReplicaConnection::class);
        $connection
            ->expects(self::atLeastOnce())
            ->method('ensureConnectedToPrimary');

        $connection
            ->expects(self::atLeastOnce())
            ->method('createSchemaManager')
            ->willReturn($this->connection->createSchemaManager());

        $connection
            ->expects(self::atLeastOnce())
            ->method('getDatabasePlatform')
            ->willReturn($this->connection->getDatabasePlatform());

        $storage = new TableMetadataStorage($connection, new AlphabeticalComparator(), $this->config);
        $storage->ensureInitialized();
    }

    public function testMigratedVersionUpdate(): void
    {
        $this->connection->insert($this->config->getTableName(), [$this->config->getVersionColumnName() => '1234']);
        $this->connection->insert($this->config->getTableName(), [$this->config->getVersionColumnName() => '5678']);

        $this->storage->ensureInitialized();

        $rows = $this->connection->fetchAllAssociative(sprintf(
            'SELECT * FROM %s ORDER BY %s ASC',
            $this->config->getTableName(),
            $this->config->getVersionColumnName()
        ));

        self::assertCount(2, $rows);

        self::assertSame([
            'version' => '1234',
            'executed_at' => null,
            'execution_time' => null,
        ], $rows[0]);
        self::assertSame([
            'version' => 'Foo\\5678',
            'executed_at' => null,
            'execution_time' => null,
        ], $rows[1]);
    }
}
