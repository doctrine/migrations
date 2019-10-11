<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\MigrationPlanCalculator;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\MigratorInterface;
use Doctrine\Migrations\QueryWriter;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
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
            'version' => '1',
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
            'version' => '1',
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
            'version' => '1',
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

        $storage = $this->createMock(MetadataStorage::class);

        $this->migrator = $this->createMock(MigratorInterface::class);

        $this->queryWriter = $this->createMock(QueryWriter::class);

        $migration = $this->createMock(AbstractMigration::class);
        $m1        = new AvailableMigration(new Version('1'), $migration);

        $migrationRepository = $this->createMock(MigrationRepository::class);
        $migrationRepository
            ->expects(self::once())
            ->method('getMigration')
            ->willReturn($m1);

        $planCalculator = new MigrationPlanCalculator($migrationRepository, $storage);

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
            ->willReturn($planCalculator);

        $this->dependencyFactory->expects(self::any())
            ->method('getQueryWriter')
            ->willReturn($this->queryWriter);

        $this->dependencyFactory->expects(self::any())
            ->method('getMigrationRepository')
            ->willReturn($migrationRepository);

        $this->executeCommand = $this->getMockBuilder(ExecuteCommand::class)
            ->setConstructorArgs([null, $this->dependencyFactory])
            ->setMethods(['canExecute'])
            ->getMock();

        $this->executeCommandTester = new CommandTester($this->executeCommand);
    }
}
