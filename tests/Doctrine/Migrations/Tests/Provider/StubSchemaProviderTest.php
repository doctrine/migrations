<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\Provider\OrmSchemaProvider;
use Doctrine\Migrations\Provider\SchemaDiffProvider;
use Doctrine\Migrations\Provider\StubSchemaProvider;
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
class StubSchemaProviderTest extends MigrationTestCase
{
    public function testCreateFromSchema()
    {
        $schema = $this->createMock(Schema::class);
        $provider = new StubSchemaProvider($schema);

        self::assertSame($schema, $provider->createSchema());
    }
}
