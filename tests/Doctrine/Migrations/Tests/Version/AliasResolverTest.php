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
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Tests\Helper;
use Doctrine\Migrations\Version\AlphabeticalComparator;
use Doctrine\Migrations\Version\CurrentMigrationStatusCalculator;
use Doctrine\Migrations\Version\DefaultAliasResolver;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\Migrations\Version\MigrationStatusCalculator;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;
use function sys_get_temp_dir;

final class AliasResolverTest extends TestCase
{
    /** @var MigrationRepository */
    private $migrationRepository;

    /** @var DefaultAliasResolver */
    private $versionAliasResolver;

    /** @var TableMetadataStorage */
    private $metadataStorage;

    /** @var MigrationStatusCalculator */
    private $statusCalculator;

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
            Helper::registerMigrationInstance($this->migrationRepository, new Version($version), $migrationClass);
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
            Helper::registerMigrationInstance($this->migrationRepository, new Version($version), $migrationClass);
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
            ['first', '0'],
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
            ['first', '0'],
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

        $this->migrationRepository = new MigrationRepository(
            [],
            [],
            new RecursiveRegexFinder('#.*\\.php$#i'),
            $versionFactory,
            new AlphabeticalComparator()
        );
        $this->metadataStorage     = new TableMetadataStorage($conn);
        $this->metadataStorage->ensureInitialized();

        $this->statusCalculator     = new CurrentMigrationStatusCalculator($this->migrationRepository, $this->metadataStorage);
        $this->versionAliasResolver = new DefaultAliasResolver(
            $this->migrationRepository,
            $this->metadataStorage,
            $this->statusCalculator
        );
    }

    private function getSqliteConnection() : Connection
    {
        $params = ['driver' => 'pdo_sqlite', 'memory' => true];

        return DriverManager::getConnection($params);
    }
}
