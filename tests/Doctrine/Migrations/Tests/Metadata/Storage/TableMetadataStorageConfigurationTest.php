<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Metadata\Storage;

use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use PHPUnit\Framework\TestCase;

class TableMetadataStorageConfigurationTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = new TableMetadataStorageConfiguration();

        self::assertSame('doctrine_migration_versions', $config->getTableName());
        self::assertSame('version', $config->getVersionColumnName());
        self::assertSame(191, $config->getVersionColumnLength());
        self::assertSame('executed_at', $config->getExecutedAtColumnName());
        self::assertSame('execution_time', $config->getExecutionTimeColumnName());
    }

    public function testConfigs(): void
    {
        $config = new TableMetadataStorageConfiguration();

        $config->setTableName('a');
        $config->setVersionColumnName('b');
        $config->setVersionColumnLength(1);
        $config->setExecutedAtColumnName('c');
        $config->setExecutionTimeColumnName('d');

        self::assertSame('a', $config->getTableName());
        self::assertSame('b', $config->getVersionColumnName());
        self::assertSame(1, $config->getVersionColumnLength());
        self::assertSame('c', $config->getExecutedAtColumnName());
        self::assertSame('d', $config->getExecutionTimeColumnName());
    }
}
