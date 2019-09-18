<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use DateTimeImmutable;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tests\TestLogger;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\Migrations\Tools\Console\Command\VersionCommand;
use Doctrine\Migrations\Tools\Console\Helper\MigrationStatusInfosHelper;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class StatusCommandTest extends MigrationTestCase
{
    /** @var VersionCommand */
    private $command;

    /**
     * @var MigrationRepository
     */
    private $migrationRepository;

    /**
     * @var \Doctrine\Migrations\Metadata\Storage\MetadataStorage
     */
    private $metadataStorage;

    /**
     * @var CommandTester
     */
    private $commandTester;

    protected function setUp(): void
    {
        $configuration = new Configuration();
        $configuration->setMetadataStorageConfiguration(new TableMetadataStorageConfiguration());
        $configuration->addMigrationsDirectory('DoctrineMigrations', sys_get_temp_dir());

        $conn = $this->getSqliteConnection();

        $dependencyFactory = new DependencyFactory($configuration, $conn);

        $this->migrationRepository = $dependencyFactory->getMigrationRepository();
        $this->metadataStorage = $dependencyFactory->getMetadataStorage();

        $this->command = new StatusCommand(null, $dependencyFactory);
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecute(): void
    {
        $result = new ExecutionResult(new Version('1230'), Direction::UP);
        $this->metadataStorage->complete($result);

        $result = new ExecutionResult(new Version('1233'), Direction::UP);
        $this->metadataStorage->complete($result);

        $this->commandTester->execute(
            [
            ],
            ['interactive' => false]
        );

        $lines = array_map('trim', explode("\n", trim($this->commandTester->getDisplay())));

        self::assertSame(array(
                0 => '== Configuration',
                1 => '',
                2 => '>> Name:                                               Doctrine Database Migrations',
                3 => '>> Database Driver:                                    pdo_sqlite',
                4 => '>> Database Host:',
                5 => '>> Database Name:',
                6 => '>> Configuration Source:                               manually configured',
                7 => '>> Version storage:                                    Doctrine\\Migrations\\Metadata\\Storage\\TableMetadataStorageConfiguration',
                8 => '>> Previous Version:                                   1230',
                9 => '>> Current Version:                                    1233',
                10 => '>> Next Version:                                       Already at latest version',
                11 => '>> Latest Version:',
                12 => '>> Executed Migrations:                                2',
                13 => '>> Executed Unavailable Migrations:                    2',
                14 => '>> Available Migrations:                               0',
                15 => '>> New Migrations:                                     0',
                16 => '>> Version Table Name:                                 doctrine_migration_versions',
                17 => '>> Version Column Name:                                version',
            )

            , $lines);
    }

    public function testExecuteDetails(): void
    {
        $migrationClass = $this->createMock(AbstractMigration::class);
        $this->migrationRepository->registerMigrationInstance(new Version('1231'), $migrationClass);

        $result = new ExecutionResult(new Version('1230'), Direction::UP);
        $this->metadataStorage->complete($result);

        $result = new ExecutionResult(new Version('1233'), Direction::UP);
        $this->metadataStorage->complete($result);

        $this->commandTester->execute(
            [
                '--show-versions' => true
            ],
            ['interactive' => false]
        );

        $lines = array_map('trim', explode("\n", trim($this->commandTester->getDisplay())));

        self::assertSame(array(
            0 => '== Configuration',
            1 => '',
            2 => '>> Name:                                               Doctrine Database Migrations',
            3 => '>> Database Driver:                                    pdo_sqlite',
            4 => '>> Database Host:',
            5 => '>> Database Name:',
            6 => '>> Configuration Source:                               manually configured',
            7 => '>> Version storage:                                    Doctrine\\Migrations\\Metadata\\Storage\\TableMetadataStorageConfiguration',
            8 => '>> Previous Version:                                   1230',
            9 => '>> Current Version:                                    1233',
            10 => '>> Next Version:                                       1231',
            11 => '>> Latest Version:                                     1231',
            12 => '>> Executed Migrations:                                2',
            13 => '>> Executed Unavailable Migrations:                    2',
            14 => '>> Available Migrations:                               1',
            15 => '>> New Migrations:                                     1',
            16 => '>> Version Table Name:                                 doctrine_migration_versions',
            17 => '>> Version Column Name:                                version',
            18 => '',
            19 => '== Available Migration Versions',
            20 => '',
            21 => '>> 1231                                             not migrated',
            22 => '',
            23 => '== Previously Executed Unavailable Migration Versions',
            24 => '',
            25 => '>> 1230',
            26 => '>> 1233',
        ), $lines);
    }
}
