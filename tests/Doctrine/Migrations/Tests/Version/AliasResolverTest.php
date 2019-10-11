<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Version;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Exception\NoMigrationsFoundWithCriteria;
use Doctrine\Migrations\Exception\UnknownMigrationVersion;
use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\MigrationPlanCalculator;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Version\AliasResolver;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;
use function sys_get_temp_dir;

final class AliasResolverTest extends TestCase
{
    /** @var MigrationRepository */
    private $migrationRepository;

    /** @var AliasResolver */
    private $versionAliasResolver;

    /** @var TableMetadataStorage */
    private $metadataStorage;

    /** @var MigrationPlanCalculator */
    private $planCalculator;

    /**
     * @dataProvider getAliases
     */
    public function testAliases(string $alias, ?string $expectedVersion, ?string $expectedException = null) : void
    {
        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }
        $migrationClass = $this->createMock(AbstractMigration::class);
        foreach (['A', 'B', 'C'] as $version) {
            $this->migrationRepository->registerMigrationInstance(new Version($version), $migrationClass);
        }

        foreach (['A', 'B'] as $version) {
            $result = new ExecutionResult(new Version($version), Direction::UP);
            $this->metadataStorage->complete($result);
        }

        $resolvedAlias = $this->versionAliasResolver->resolveVersionAlias($alias);
        if ($expectedVersion === null) {
            return;
        }

        self::assertEquals(new Version($expectedVersion), $resolvedAlias);
    }

    /**
     * @dataProvider getAliasesWithNoExecuted
     */
    public function testAliasesWithNoExecuted(string $alias, ?string $expectedVersion, ?string $expectedException = null) : void
    {
        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $migrationClass = $this->createMock(AbstractMigration::class);
        foreach (['A', 'B', 'C'] as $version) {
            $this->migrationRepository->registerMigrationInstance(new Version($version), $migrationClass);
        }
        $resolvedAlias = $this->versionAliasResolver->resolveVersionAlias($alias);
        if ($expectedVersion === null) {
            return;
        }

        self::assertEquals(new Version($expectedVersion), $resolvedAlias);
    }

    /**
     * @return mixed[][]
     */
    public function getAliasesWithNoExecuted() : array
    {
        return [
            ['first', 'A'],
            ['current', '0'],
            ['prev', '0'],
            ['next', 'A'],
            ['latest', 'C'],
            ['current-1', null, NoMigrationsFoundWithCriteria::class],
            ['current+1', 'A'],
            ['B', 'B'],
            ['X', null, UnknownMigrationVersion::class],
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
            ['X', null, UnknownMigrationVersion::class],
        ];
    }

    protected function setUp() : void
    {
        $configuration = new Configuration();
        $configuration->setMetadataStorageConfiguration(new TableMetadataStorageConfiguration());
        $configuration->addMigrationsDirectory('DoctrineMigrations', sys_get_temp_dir());

        $conn = $this->getSqliteConnection();

        $versionFactory = $this->createMock(MigrationFactory::class);

        $this->migrationRepository  = new MigrationRepository(
            [],
            [],
            new RecursiveRegexFinder('#.*\\.php$#i'),
            $versionFactory
        );
        $this->metadataStorage      = new TableMetadataStorage($conn);
        $this->planCalculator       = new MigrationPlanCalculator($this->migrationRepository, $this->metadataStorage);
        $this->versionAliasResolver = new AliasResolver(
            $this->migrationRepository,
            $this->metadataStorage,
            $this->planCalculator
        );
    }

    private function getSqliteConnection() : Connection
    {
        $params = ['driver' => 'pdo_sqlite', 'memory' => true];

        return DriverManager::getConnection($params);
    }
}
