<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Migrator;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\QueryWriter;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\MigrationPlanCalculator;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use function getcwd;
use function sys_get_temp_dir;

class ExecuteCommandTest extends TestCase
{
    /** @var ExecuteCommand|MockObject */
    private $executeCommand;

    /** @var MockObject|DependencyFactory */
    private $dependencyFactory;

    /** @var CommandTester */
    private $executeCommandTester;

    /** @var MockObject */
    private $migrator;

    /** @var MockObject */
    private $queryWriter;

    /** @var MigrationPlanCalculator|MockObject */
    private $planCalculator;

    /**
     * @param mixed $arg
     *
     * @dataProvider getWriteSqlValues
     */
    public function testWriteSql($arg, string $path) : void
    {
        $this->migrator
            ->expects(self::once())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration) {
                self::assertTrue($configuration->isDryRun());

                return ['A'];
            });

        $this->queryWriter->expects(self::once())
            ->method('write')
            ->with($path, 'down', ['A']);

        $this->executeCommandTester->execute([
            'versions' => ['1'],
            '--down' => true,
            '--write-sql' => $arg,
        ]);

        self::assertSame(0, $this->executeCommandTester->getStatusCode());
    }

    /**
     * @return mixed[]
     */
    public function getWriteSqlValues() : array
    {
        return [
            [true, getcwd()],
            [ __DIR__ . '/_files', __DIR__ . '/_files'],
        ];
    }

    public function testExecute() : void
    {
        $this->executeCommand->expects(self::once())
            ->method('canExecute')
            ->willReturn(true);

        $this->migrator
            ->expects(self::once())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration) {
                self::assertFalse($configuration->isDryRun());

                return ['A'];
            });

        $this->executeCommandTester->execute([
            'versions' => ['1'],
            '--down' => true,
        ]);

        self::assertSame(0, $this->executeCommandTester->getStatusCode());
    }

    public function testExecuteMultiple() : void
    {
        $migration = $this->createMock(AbstractMigration::class);
        $p1        = new MigrationPlan(new Version('1'), $migration, Direction::UP);
        $pl        = new MigrationPlanList([$p1], Direction::UP);

        $this->planCalculator
            ->expects(self::once())
            ->method('getPlanForVersions')
            ->with([new Version('1'), new Version('2')])
            ->willReturn($pl);

        $this->executeCommand->expects(self::once())
            ->method('canExecute')
            ->willReturn(true);

        $this->migrator
            ->expects(self::once())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration) {
                self::assertFalse($configuration->isDryRun());

                return ['A'];
            });

        $this->executeCommandTester->execute([
            'versions' => ['1', '2'],
            '--down' => true,
        ]);

        self::assertSame(0, $this->executeCommandTester->getStatusCode());
    }

    public function testExecuteCancel() : void
    {
        $this->executeCommand->expects(self::once())
            ->method('canExecute')
            ->willReturn(false);

        $this->migrator
            ->expects(self::never())
            ->method('migrate')
            ->willReturnCallback(static function (MigrationPlanList $planList, MigratorConfiguration $configuration) {
                self::assertFalse($configuration->isDryRun());

                return ['A'];
            });

        $this->executeCommandTester->execute([
            'versions' => ['1'],
            '--down' => true,
        ]);

        self::assertSame(1, $this->executeCommandTester->getStatusCode());
    }

    protected function setUp() : void
    {
        $this->dependencyFactory = $this->getMockBuilder(DependencyFactory::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['getConsoleInputMigratorConfigurationFactory'])
            ->getMock();

        $this->migrator = $this->createMock(Migrator::class);

        $this->queryWriter = $this->createMock(QueryWriter::class);

        $migration = $this->createMock(AbstractMigration::class);

        $p1 = new MigrationPlan(new Version('1'), $migration, Direction::UP);
        $pl = new MigrationPlanList([$p1], Direction::UP);

        $this->planCalculator = $this->createMock(MigrationPlanCalculator::class);
        $this->planCalculator
            ->expects(self::once())
            ->method('getPlanForVersions')
            ->willReturn($pl);

        $configuration = new Configuration();
        $configuration->setMetadataStorageConfiguration(new TableMetadataStorageConfiguration());
        $configuration->addMigrationsDirectory('DoctrineMigrations', sys_get_temp_dir());

        $this->dependencyFactory->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $this->dependencyFactory->expects(self::once())
            ->method('getMigrator')
            ->willReturn($this->migrator);

        $this->dependencyFactory->expects(self::once())
            ->method('getMigrationPlanCalculator')
            ->willReturn($this->planCalculator);

        $this->dependencyFactory->expects(self::any())
            ->method('getQueryWriter')
            ->willReturn($this->queryWriter);

        $this->executeCommand = $this->getMockBuilder(ExecuteCommand::class)
            ->setConstructorArgs([null, $this->dependencyFactory])
            ->onlyMethods(['canExecute'])
            ->getMock();

        $this->executeCommandTester = new CommandTester($this->executeCommand);
    }
}
