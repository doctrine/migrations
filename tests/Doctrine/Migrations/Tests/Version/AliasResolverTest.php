<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Version;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Version\AliasResolver;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Factory;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AliasResolverTest extends TestCase
{
    /** @var MigrationRepository|MockObject */
    private $migrationRepository;

    /** @var AliasResolver */
    private $versionAliasResolver;

    /**
     * @var TableMetadataStorage
     */
    private $metadataStorage;

    /**
     * @dataProvider getAliases
     */
    public function testAliases(string $alias, ?string $expectedVersion)
    {
        $migrationClass = $this->createMock(AbstractMigration::class);
        foreach (['A', 'B', 'C'] as $version) {
            $this->migrationRepository->registerMigrationInstance(new Version($version), $migrationClass);
        }

        foreach (['A', 'B'] as $version){
            $result = new ExecutionResult(new Version($version), Direction::UP);
            $this->metadataStorage->complete($result);
        }

        self::assertEquals($expectedVersion !== null? new Version($expectedVersion) : null, $this->versionAliasResolver->resolveVersionAlias($alias));
    }

    /**
     * @dataProvider getAliasesWithNoExecuted
     */
    public function testAliasesWithNoExecuted(string $alias, ?string $expectedVersion)
    {
        $migrationClass = $this->createMock(AbstractMigration::class);
        foreach (['A', 'B', 'C'] as $version) {
            $this->migrationRepository->registerMigrationInstance(new Version($version), $migrationClass);
        }

        self::assertEquals($expectedVersion!== null ? new Version($expectedVersion) : null, $this->versionAliasResolver->resolveVersionAlias($alias));
    }
    /**
     * @return mixed[][]
     */
    public function getAliasesWithNoExecuted() : array
    {
        return [
            ['first', 'A'],
            ['current', null],
            ['prev', '0'],
            ['next', 'A'],
            ['latest', 'C'],
            ['current-1', null],
            ['current+1', 'A'],
            ['B', 'B'],
            ['X', null],
        ];
    }

    /**
     * @return mixed[][]
     */
    public function getAliases() : array
    {
        return [
            ['first', 'A'],
            ['current', 'B'],
            ['prev', 'A'],
            ['next', 'C'],
            ['latest', 'C'],
            ['current-1', 'A'],
            ['current+1', 'C'],
            ['B', 'B'],
            ['X', null],
        ];
    }

    protected function setUp() : void
    {
        $configuration = new Configuration();
        $configuration->setMetadataStorageConfiguration(new TableMetadataStorageConfiguration());
        $configuration->addMigrationsDirectory('DoctrineMigrations', sys_get_temp_dir());

        $conn = $this->getSqliteConnection();

        $versionFactory  = $this->createMock(Factory::class);

        $this->migrationRepository = new MigrationRepository(
            [],
            new RecursiveRegexFinder('#.*\\.php$#i'),
            $versionFactory
        );
        $this->metadataStorage = new TableMetadataStorage($conn);

        $this->versionAliasResolver = new AliasResolver($this->migrationRepository, $this->metadataStorage);
    }

    private function getSqliteConnection() : Connection
    {
        $params = ['driver' => 'pdo_sqlite', 'memory' => true];

        return DriverManager::getConnection($params);
    }
}
