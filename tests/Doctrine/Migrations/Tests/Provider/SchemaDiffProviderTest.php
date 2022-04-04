<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Provider;

use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\Provider\DBALSchemaDiffProvider;
use Doctrine\Migrations\Provider\SchemaDiffProvider;
use Doctrine\Migrations\Tests\MigrationTestCase;

/**
 * Tests the OrmSchemaProvider using a real entity manager.
 */
class SchemaDiffProviderTest extends MigrationTestCase
{
    private SchemaDiffProvider $provider;

    public function testCreateFromSchema(): void
    {
        $schema = $this->provider->createFromSchema();

        self::assertTrue($schema->hasTable('foo'));
    }

    public function testGetSqlDiffToMigrate(): void
    {
        $oldSchema = $this->provider->createFromSchema();

        $newSchema = $this->provider->createToSchema($oldSchema);
        $newSchema->dropTable('foo');

        $queries = $this->provider->getSqlDiffToMigrate($oldSchema, $newSchema);

        self::assertContains('DROP TABLE foo', $queries);
        self::assertContains('DROP TABLE foo', $queries);
    }

    protected function setUp(): void
    {
        $conn           = $this->getSqliteConnection();
        $schemaManager  = $conn->createSchemaManager();
        $this->provider = new DBALSchemaDiffProvider($schemaManager, $conn->getDatabasePlatform());

        $schemaChangelog = new Table('foo');
        $schemaChangelog->addColumn('a', 'string');
        $schemaChangelog->addColumn('b', 'string');
        $schemaManager->createTable($schemaChangelog);
    }
}
