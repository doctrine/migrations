<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Provider;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Name\OptionallyQualifiedName;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\Provider\DBALSchemaDiffProvider;
use Doctrine\Migrations\Provider\SchemaDiffProvider;
use Doctrine\Migrations\Tests\MigrationTestCase;

use function method_exists;

/**
 * Tests the OrmSchemaProvider using a real entity manager.
 */
class SchemaDiffProviderTest extends MigrationTestCase
{
    protected SchemaDiffProvider $provider;

    public function testCreateFromSchema(): void
    {
        $schema = $this->provider->createFromSchema();

        self::assertTrue($schema->hasTable('foo'));
    }

    public function testGetSqlDiffToMigrate(): void
    {
        $oldSchema = $this->provider->createFromSchema();

        $newSchema = $this->provider->createToSchema($oldSchema);
        // @phpstan-ignore function.alreadyNarrowedType
        if (method_exists(Schema::class, 'editor')) {
            $newSchema = $newSchema->edit()->dropTable(OptionallyQualifiedName::unquoted('foo'))->create();
        } else {
            // @phpstan-ignore method.deprecated
            $newSchema->dropTable('foo');
        }

        $queries = $this->provider->getSqlDiffToMigrate($oldSchema, $newSchema);

        self::assertContains('DROP TABLE foo', $queries);
        self::assertContains('DROP TABLE foo', $queries);
    }

    protected function setUp(): void
    {
        $conn           = $this->getSqliteConnection();
        $schemaManager  = $conn->createSchemaManager();
        $this->provider = new DBALSchemaDiffProvider($schemaManager, $conn->getDatabasePlatform());

        // @phpstan-ignore function.alreadyNarrowedType
        if (method_exists(Table::class, 'editor')) {
            $schemaChangelog = Table::editor()
                ->setName(OptionallyQualifiedName::unquoted('foo'))
                ->addColumn(
                    Column::editor()
                        ->setName(UnqualifiedName::unquoted('a'))
                        ->setTypeName('string')
                        ->create(),
                )
                ->addColumn(
                    Column::editor()
                        ->setName(UnqualifiedName::unquoted('b'))
                        ->setTypeName('string')
                        ->create(),
                )
                ->create();
        } else {
            $schemaChangelog = new Table('foo');
            // @phpstan-ignore method.deprecated
            $schemaChangelog->addColumn('a', 'string');
            // @phpstan-ignore method.deprecated
            $schemaChangelog->addColumn('b', 'string');
        }

        $schemaManager->createTable($schemaChangelog);
    }
}
