<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Tests\Helper;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Command\CurrentCommand;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use Symfony\Component\Console\Tester\CommandTester;

use function array_map;
use function explode;
use function sys_get_temp_dir;
use function trim;

class CurrentCommandTest extends MigrationTestCase
{
    private CurrentCommand $command;

    private MigrationsRepository $migrationRepository;

    private MetadataStorage $metadataStorage;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $configuration = new Configuration();
        $configuration->setMetadataStorageConfiguration(new TableMetadataStorageConfiguration());
        $configuration->addMigrationsDirectory('DoctrineMigrations', sys_get_temp_dir());

        $conn = $this->getSqliteConnection();

        $dependencyFactory = DependencyFactory::fromConnection(
            new ExistingConfiguration($configuration),
            new ExistingConnection($conn)
        );

        $this->migrationRepository = $dependencyFactory->getMigrationRepository();
        $this->metadataStorage     = $dependencyFactory->getMetadataStorage();
        $this->metadataStorage->ensureInitialized();

        $this->command       = new CurrentCommand($dependencyFactory);
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecute(): void
    {
        $migrationClass = $this->createMock(AbstractMigration::class);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1231'), $migrationClass);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1230'), $migrationClass);

        $this->metadataStorage->complete(new ExecutionResult(new Version('1231')));

        $this->commandTester->execute(
            [],
            ['interactive' => false]
        );

        $lines = array_map('trim', explode("\n", trim($this->commandTester->getDisplay(true))));
        self::assertSame('1231', $lines[0]);
    }

    public function testExecuteWhenNoMigrations(): void
    {
        $this->commandTester->execute(
            [],
            ['interactive' => false]
        );

        $lines = array_map('trim', explode("\n", trim($this->commandTester->getDisplay(true))));
        self::assertSame('0 - (No migration executed yet)', $lines[0]);
    }

    public function testExecuteWhenMissingMigration(): void
    {
        $this->metadataStorage->complete(new ExecutionResult(new Version('missing')));

        $this->commandTester->execute(
            [],
            ['interactive' => false]
        );

        $lines = array_map('trim', explode("\n", trim($this->commandTester->getDisplay(true))));
        self::assertSame('missing - (Migration info not available)', $lines[0]);
    }
}
