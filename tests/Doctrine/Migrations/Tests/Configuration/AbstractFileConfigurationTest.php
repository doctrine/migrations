<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\AbstractFileConfiguration;
use Doctrine\Migrations\Configuration\Exception\InvalidConfigurationKey;
use PHPUnit\Framework\TestCase;
use function basename;
use function chdir;
use function getcwd;

class AbstractFileConfigurationTest extends TestCase
{
    /** @var Connection */
    private $connection;

    /** @var AbstractFileConfiguration */
    private $fileConfiguration;

    public function testLoadChecksCurrentWorkingDirectory() : void
    {
        $cwd = getcwd();

        chdir(__DIR__);

        $file = basename(__FILE__);

        $this->fileConfiguration->load($file);

        self::assertSame(__DIR__ . '/' . $file, $this->fileConfiguration->getFile());

        chdir($cwd);
    }

    public function testSetConfiguration() : void
    {
        $fileConfiguration = $this->createPartialMock(TestAbstractFileConfiguration::class, [
            'setMigrationsNamespace',
            'setMigrationsTableName',
            'setMigrationsColumnName',
            'setMigrationsColumnLength',
            'setMigrationsExecutedAtColumnName',
            'setMigrationsAreOrganizedByYearAndMonth',
            'setName',
            'setMigrationsDirectory',
            'registerMigrationsFromDirectory',
            'registerMigration',
            'setCustomTemplate',
            'setAllOrNothing',
        ]);

        $fileConfiguration->expects(self::once())
            ->method('setMigrationsNamespace')
            ->with('Doctrine');

        $fileConfiguration->expects(self::once())
            ->method('setMigrationsTableName')
            ->with('migration_version');

        $fileConfiguration->expects(self::once())
            ->method('setMigrationsColumnName')
            ->with('version_number');

        $fileConfiguration->expects(self::once())
            ->method('setMigrationsColumnLength')
            ->with(200);

        $fileConfiguration->expects(self::once())
            ->method('setMigrationsExecutedAtColumnName')
            ->with('executed_at');

        $fileConfiguration->expects(self::once())
            ->method('setMigrationsAreOrganizedByYearAndMonth');

        $fileConfiguration->expects(self::once())
            ->method('setName')
            ->with('Migrations Test');

        $fileConfiguration->expects(self::once())
            ->method('setMigrationsDirectory')
            ->with('migrations_directory');

        $fileConfiguration->expects(self::once())
            ->method('registerMigrationsFromDirectory')
            ->with('migrations_directory');

        $fileConfiguration->expects(self::once())
            ->method('registerMigration')
            ->with('001', 'Test');

        $fileConfiguration->expects(self::once())
            ->method('setCustomTemplate')
            ->with('custom_template');

        $fileConfiguration->expects(self::once())
            ->method('setAllOrNothing')
            ->with(true);

        $fileConfiguration->setTestConfiguration([
            'migrations_namespace'      => 'Doctrine',
            'table_name'                => 'migration_version',
            'column_name'               => 'version_number',
            'column_length'             => 200,
            'executed_at_column_name'   => 'executed_at',
            'organize_migrations'       => 'year_and_month',
            'name'                      => 'Migrations Test',
            'migrations_directory'      => 'migrations_directory',
            'migrations'                => [
                [
                    'version' => '001',
                    'class'   => 'Test',
                ],
            ],
            'custom_template' => 'custom_template',
            'all_or_nothing' => true,
        ]);
    }

    public function testSetConfigurationThrowsInvalidConfigurationKey() : void
    {
        $this->expectException(InvalidConfigurationKey::class);
        $this->expectExceptionMessage('Migrations configuration key "unknown" does not exist.');

        $this->fileConfiguration->setTestConfiguration(['unknown' => 'value']);
    }

    protected function setUp() : void
    {
        $this->connection = $this->createMock(Connection::class);

        $this->fileConfiguration = new TestAbstractFileConfiguration($this->connection);
    }
}

class TestAbstractFileConfiguration extends AbstractFileConfiguration
{
    /**
     * @param mixed[] $config
     */
    public function setTestConfiguration(array $config) : void
    {
        $this->setConfiguration($config);
    }

    protected function doLoad(string $file) : void
    {
    }
}
