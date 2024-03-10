<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Provider;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Provider\StubSchemaProvider;
use Doctrine\Migrations\Tests\MigrationTestCase;

/**
 * Tests the OrmSchemaProvider using a real entity manager.
 */
class StubSchemaProviderTest extends MigrationTestCase
{
    public function testCreateFromSchema(): void
    {
        $schema   = $this->createMock(Schema::class);
        $provider = new StubSchemaProvider($schema);

        self::assertSame($schema, $provider->createSchema());
    }
}
