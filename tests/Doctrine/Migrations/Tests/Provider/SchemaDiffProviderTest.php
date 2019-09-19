<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\Provider\OrmSchemaProvider;
use Doctrine\Migrations\Provider\SchemaDiffProvider;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Tools\Setup;
use UnexpectedValueException;

/**
 * Tests the OrmSchemaProvider using a real entity manager.
 */
class SchemaDiffProviderTest extends MigrationTestCase
{
    /**
     * @var SchemaDiffProvider
     */
    private $provider;

    public function testCreateFromSchema()
    {
        $schema = $this->provider->createFromSchema();

        self::assertTrue($schema->hasTable('foo'));
    }

    public function testGetSqlDiffToMigrate()
    {
        $oldSchema = $this->provider->createFromSchema();

        $newSchema = $this->provider->createToSchema($oldSchema);
        $newSchema->dropTable('foo');

        $queries = $this->provider->getSqlDiffToMigrate($oldSchema, $newSchema);

        self::assertContains("DROP TABLE foo", $queries);
        self::assertContains("DROP TABLE foo", $queries);
    }

    protected function setUp(): void
    {
        $conn = $this->getSqliteConnection();
        $schemaManager = $conn->getSchemaManager();
        $this->provider = new SchemaDiffProvider($schemaManager, $conn->getDatabasePlatform());

        $schemaChangelog = new Table('foo');
        $schemaChangelog->addColumn('a', 'string');
        $schemaChangelog->addColumn('b', 'string');
        $schemaManager->createTable($schemaChangelog);
    }
}
