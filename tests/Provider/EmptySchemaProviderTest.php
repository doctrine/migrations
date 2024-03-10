<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Provider;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\Migrations\Provider\EmptySchemaProvider;
use Doctrine\Migrations\Tests\MigrationTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests the EmptySchemaProvider.
 */
class EmptySchemaProviderTest extends MigrationTestCase
{
    /** @var AbstractSchemaManager<AbstractPlatform>&MockObject */
    private AbstractSchemaManager $schemaManager;

    private EmptySchemaProvider $emptyProvider;

    public function testCreateSchema(): void
    {
        $schemaConfig = new SchemaConfig();

        $this->schemaManager->expects(self::once())
            ->method('createSchemaConfig')
            ->willReturn($schemaConfig);

        $schema = $this->emptyProvider->createSchema();

        self::assertSame([], $schema->getTables());
        self::assertSame([], $schema->getSequences());
        self::assertSame([], $schema->getNamespaces());
    }

    protected function setUp(): void
    {
        $this->schemaManager = $this->createMock(AbstractSchemaManager::class);
        $this->emptyProvider = new EmptySchemaProvider($this->schemaManager);
    }
}
