<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Metadata\Storage;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;
use function sprintf;

class ExistingTableMetadataStorageTest extends TestCase
{
    /** @var Connection */
    private $connection;

    /** @var TableMetadataStorage */
    private $storage;

    /** @var TableMetadataStorageConfiguration */
    private $config;

    /** @var AbstractSchemaManager */
    private $schemaManager;

    /** @var MigrationRepository */
    private $migrationRepository;

    private function getSqliteConnection() : Connection
    {
        $params = ['driver' => 'pdo_sqlite', 'memory' => true];

        return DriverManager::getConnection($params);
    }

    public function setUp() : void
    {
        $this->connection    = $this->getSqliteConnection();
        $this->schemaManager = $this->connection->getSchemaManager();

        $migration                 = $this->createMock(AbstractMigration::class);
        $versionFactory            = $this->createMock(MigrationFactory::class);
        $this->migrationRepository = new MigrationRepository(
            [],
            [],
            new RecursiveRegexFinder('#.*\\.php$#i'),
            $versionFactory
        );
        $this->migrationRepository->registerMigrationInstance(new Version('Foo\\5678'), $migration);

        $this->config = new TableMetadataStorageConfiguration();

        $this->storage = new TableMetadataStorage($this->connection, $this->config, $this->migrationRepository);

        // create partial table
        $table = new Table($this->config->getTableName());
        $table->addColumn($this->config->getVersionColumnName(), 'string', ['notnull' => true, 'length' => 24]);
        $table->setPrimaryKey([ $this->config->getVersionColumnName()]);
        $this->schemaManager->createTable($table);
    }

    public function testMigratedVersionUpdate() : void
    {
        $this->connection->insert($this->config->getTableName(), [$this->config->getVersionColumnName() => '1234']);
        $this->connection->insert($this->config->getTableName(), [$this->config->getVersionColumnName() => '5678']);

        $this->storage->ensureInitialized();

        $rows = $this->connection->fetchAll(sprintf(
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
