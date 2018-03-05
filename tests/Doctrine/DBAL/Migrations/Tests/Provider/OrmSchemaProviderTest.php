<?php

namespace Doctrine\DBAL\Migrations\Tests\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Provider\OrmSchemaProvider;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Tools\Setup;

/**
 * Tests the OrmSchemaProvider using a real entity manager.
 */
class OrmSchemaProviderTest extends MigrationTestCase
{
    /** @var Connection */
    private $conn;

    /** @var Configuration */
    private $config;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var OrmSchemaProvider */
    private $ormProvider;

    protected function setUp()
    {
        $this->conn          = $this->getSqliteConnection();
        $this->config        = Setup::createXMLMetadataConfiguration([__DIR__ . '/_files'], true);
        $this->entityManager = EntityManager::create($this->conn, $this->config);
        $this->ormProvider   = new OrmSchemaProvider($this->entityManager);
    }

    public function testCreateSchemaFetchesMetadataFromEntityManager()
    {
        $schema = $this->ormProvider->createSchema();
        self::assertInstanceOf(Schema::class, $schema);
        self::assertTrue($schema->hasTable('sample_entity'));
        $table = $schema->getTable('sample_entity');
        self::assertTrue($table->hasColumn('id'));
    }

    public function testEntityManagerWithoutMetadataCausesError()
    {
        $this->expectException(\UnexpectedValueException::class);

        $this->config->setMetadataDriverImpl(new XmlDriver([]));

        $this->ormProvider->createSchema();
    }

    /**
     * @return mixed[][]
     */
    public function notEntityManagers()
    {
        return [
            [new \stdClass()],
            [false],
            [1],
            ['oops'],
            [1.0],
        ];
    }

    /**
     * @dataProvider notEntityManagers
     */
    public function testPassingAnInvalidEntityManagerToConstructorCausesError($em)
    {
        $this->expectException(\InvalidArgumentException::class);

        new OrmSchemaProvider($em);
    }
}
