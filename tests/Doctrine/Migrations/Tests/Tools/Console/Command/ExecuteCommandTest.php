<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Exception\UnknownMigrationVersion;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Migrator;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\QueryWriter;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\Migrations\Version\AliasResolver;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\MigrationPlanCalculator;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;

use function getcwd;
use function sys_get_temp_dir;
use function trim;

class ExecuteCommandTest extends MigrationTestCase
{
    /** @var ExecuteCommand */
    private $executeCommand;

    /** @var DependencyFactory */
    private $dependencyFactory;

    /** @var CommandTester */
    private $executeCommandTester;

    /** @var MockObject */
    private $migrator;

    /** @var MockObject */
    private $queryWriter;

    /** @var MigrationPlanCalculator|MockObject */
    private $planCalculator;

    /** @var AliasResolver|MockObject */
    private $versionAliasResolver;

    /**
     * @param bool|string|null $arg
     *
     * @dataProvider getWriteSqlValues
     */
    public function testWriteSql(bool $dryRun, $arg, ?string $path): void
    {
        $this->migrator
            ->expects(self::once())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration) use ($dryRun): array {
                self::assertSame($dryRun, $configuration->isDryRun());

                return ['A'];
            });

        $this->versionAliasResolver
            ->expects(self::once())
            ->method('resolveVersionAlias')
            ->with('1')
            ->willReturn(new Version('1'));

        if ($arg === false) {
            $this->queryWriter
                ->expects(self::never())
                ->method('write');
        } else {
            $this->queryWriter
                ->expects(self::once())
                ->method('write')
                ->with($path, 'down', ['A']);
        }

        $this->executeCommandTester->execute([
            'versions' => ['1'],
            '--down' => true,
            '--write-sql' => $arg,
            '--dry-run' => $dryRun,
        ], ['interactive' => false]);

        self::assertSame(0, $this->executeCommandTester->getStatusCode());
    }

    /**
     * @return mixed[]
     */
    public function getWriteSqlValues(): array
    {
        return [
            // dry-run, write-path, path
            [true, false, null],
            [true, null, getcwd()],
            [true,  __DIR__ . '/_files', __DIR__ . '/_files'],
            [true,  __DIR__ . '/_files/run.sql', __DIR__ . '/_files/run.sql'],

            [false, false, null],
            [false, null, getcwd()],
            [false,  __DIR__ . '/_files', __DIR__ . '/_files'],
            [true,  __DIR__ . '/_files/run.sql', __DIR__ . '/_files/run.sql'],
        ];
    }

    public function testExecute(): void
    {
        $this->executeCommandTester->setInputs(['yes']);

        $this->migrator
            ->expects(self::once())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration): array {
                self::assertFalse($configuration->isDryRun());

                return ['A'];
            });

        $this->versionAliasResolver
            ->expects(self::once())
            ->method('resolveVersionAlias')
            ->with('1')
            ->willReturn(new Version('1'));

        $this->executeCommandTester->execute([
            'versions' => ['1'],
            '--down' => true,
        ]);

        self::assertSame(0, $this->executeCommandTester->getStatusCode());
        self::assertStringContainsString('[notice] Executing 1 up', trim($this->executeCommandTester->getDisplay(true)));
    }

    public function testExecuteVersionAlias(): void
    {
        $this->executeCommandTester->setInputs(['yes']);

        $this->migrator
            ->expects(self::once())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration): array {
                self::assertFalse($configuration->isDryRun());

                return ['A'];
            });

        $this->versionAliasResolver
            ->expects(self::once())
            ->method('resolveVersionAlias')
            ->with('current')
            ->willReturn(new Version('1'));

        $this->executeCommandTester->execute([
            'versions' => ['current'],
            '--down' => true,
        ]);

        self::assertSame(0, $this->executeCommandTester->getStatusCode());
        self::assertStringContainsString('[notice] Executing 1 up', trim($this->executeCommandTester->getDisplay(true)));
    }

    public function testExecuteVersionAliasException(): void
    {
        $this->executeCommandTester->setInputs(['yes']);

        $this->migrator
            ->expects(self::never())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration): array {
                self::assertFalse($configuration->isDryRun());

                return ['A'];
            });

        $exception = new UnknownMigrationVersion('not_a_valid_version_alias');

        $this->versionAliasResolver
            ->expects(self::once())
            ->method('resolveVersionAlias')
            ->with('not_a_valid_version_alias')
            ->willThrowException($exception);

        self::expectExceptionObject($exception);

        $this->executeCommandTester->execute([
            'versions' => ['not_a_valid_version_alias'],
            '--down' => true,
        ]);
    }

    public function testExecuteMultiple(): void
    {
        $migration = $this->createMock(AbstractMigration::class);
        $p1        = new MigrationPlan(new Version('1'), $migration, Direction::UP);
        $pl        = new MigrationPlanList([$p1], Direction::UP);

        $this->planCalculator
            ->expects(self::once())
            ->method('getPlanForVersions')
            ->with([new Version('1'), new Version('2')])
            ->willReturn($pl);

        $this->executeCommandTester->setInputs(['yes']);

        $this->migrator
            ->expects(self::once())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration): array {
                self::assertFalse($configuration->isDryRun());

                return ['A'];
            });

        $this->versionAliasResolver
            ->expects(self::exactly(2))
            ->method('resolveVersionAlias')
            ->withConsecutive(['1'], ['2'])
            ->willReturnOnConsecutiveCalls(new Version('1'), new Version('2'));

        $this->executeCommandTester->execute([
            'versions' => ['1', '2'],
            '--down' => true,
        ]);

        self::assertSame(0, $this->executeCommandTester->getStatusCode());
        self::assertStringContainsString('[notice] Executing 1, 2 up', trim($this->executeCommandTester->getDisplay(true)));
    }

    public function testExecuteCancel(): void
    {
        $this->executeCommandTester->setInputs(['no']);

        $this->planCalculator
            ->expects(self::never())
            ->method('getPlanForVersions');

        $this->migrator
            ->expects(self::never())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration): array {
                self::assertFalse($configuration->isDryRun());

                return ['A'];
            });

        $this->versionAliasResolver
            ->expects(self::once())
            ->method('resolveVersionAlias')
            ->with('1')
            ->willReturn(new Version('1'));

        $this->executeCommandTester->execute([
            'versions' => ['1'],
            '--down' => true,
        ]);

        self::assertSame(1, $this->executeCommandTester->getStatusCode());
    }

    protected function setUp(): void
    {
        $connection = $this->getSqliteConnection();

        $this->migrator             = $this->createMock(Migrator::class);
        $this->queryWriter          = $this->createMock(QueryWriter::class);
        $this->versionAliasResolver = $this->createMock(AliasResolver::class);
        $migration                  = $this->createMock(AbstractMigration::class);

        $p1 = new MigrationPlan(new Version('1'), $migration, Direction::UP);
        $pl = new MigrationPlanList([$p1], Direction::UP);

        $this->planCalculator = $this->createMock(MigrationPlanCalculator::class);
        $this->planCalculator
            ->method('getPlanForVersions')
            ->willReturn($pl);

        $configuration = new Configuration();
        $configuration->setMetadataStorageConfiguration(new TableMetadataStorageConfiguration());
        $configuration->addMigrationsDirectory('DoctrineMigrations', sys_get_temp_dir());

        $this->dependencyFactory = DependencyFactory::fromConnection(new ExistingConfiguration($configuration), new ExistingConnection($connection));

        $this->dependencyFactory->setService(Migrator::class, $this->migrator);
        $this->dependencyFactory->setService(MigrationPlanCalculator::class, $this->planCalculator);
        $this->dependencyFactory->setService(QueryWriter::class, $this->queryWriter);
        $this->dependencyFactory->setService(AliasResolver::class, $this->versionAliasResolver);

        $this->executeCommand = new ExecuteCommand($this->dependencyFactory);

        $this->executeCommandTester = new CommandTester($this->executeCommand);
    }
}
