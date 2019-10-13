<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Command\LatestCommand;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use Symfony\Component\Console\Tester\CommandTester;
use function array_map;
use function explode;
use function sys_get_temp_dir;
use function trim;

class LatestCommandTest extends MigrationTestCase
{
    /** @var LatestCommand */
    private $command;

    /** @var MigrationRepository */
    private $migrationRepository;

    /** @var MetadataStorage */
    private $metadataStorage;

    /** @var CommandTester */
    private $commandTester;

    protected function setUp() : void
    {
        $configuration = new Configuration();
        $configuration->setMetadataStorageConfiguration(new TableMetadataStorageConfiguration());
        $configuration->addMigrationsDirectory('DoctrineMigrations', sys_get_temp_dir());

        $conn = $this->getSqliteConnection();

        $dependencyFactory = new DependencyFactory($configuration, $conn);

        $this->migrationRepository = $dependencyFactory->getMigrationRepository();
        $this->metadataStorage     = $dependencyFactory->getMetadataStorage();

        $this->command       = new LatestCommand(null, $dependencyFactory);
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecute() : void
    {
        $migrationClass = $this->createMock(AbstractMigration::class);
        $this->migrationRepository->registerMigrationInstance(new Version('1231'), $migrationClass);
        $this->migrationRepository->registerMigrationInstance(new Version('1230'), $migrationClass);

        $this->metadataStorage->complete(new ExecutionResult(new Version('1231')));

        $this->commandTester->execute(
            [],
            ['interactive' => false]
        );

        $lines = array_map('trim', explode("\n", trim($this->commandTester->getDisplay(true))));
        self::assertSame('1231', $lines[0]);
    }
}
