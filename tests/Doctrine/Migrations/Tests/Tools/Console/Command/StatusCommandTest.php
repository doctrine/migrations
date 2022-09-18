<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use DateTimeImmutable;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use Symfony\Component\Console\Tester\CommandTester;

use function array_map;
use function explode;
use function sprintf;
use function str_pad;
use function sys_get_temp_dir;
use function trim;

class StatusCommandTest extends MigrationTestCase
{
    private StatusCommand $command;

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

        $this->metadataStorage = $dependencyFactory->getMetadataStorage();

        $this->metadataStorage->ensureInitialized();

        $this->command       = new StatusCommand($dependencyFactory);
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecute(): void
    {
        $result = new ExecutionResult(new Version('1230'), Direction::UP, new DateTimeImmutable('2010-01-01 02:03:04'));
        $result->setTime(10.0);
        $this->metadataStorage->complete($result);

        $result = new ExecutionResult(new Version('1233'), Direction::UP);
        $this->metadataStorage->complete($result);

        $this->commandTester->execute(
            [],
            ['interactive' => false]
        );

        $lines = array_map('trim', explode("\n", trim($this->commandTester->getDisplay(true))));

        self::assertSame(
            [
                '+----------------------+----------------------+------------------------------------------------------------------------+',
                '| Configuration                                                                                                        |',
                '+----------------------+----------------------+------------------------------------------------------------------------+',
                '| Storage              | Type                 | Doctrine\\Migrations\\Metadata\\Storage\\TableMetadataStorageConfiguration |',
                '|                      | Table Name           | doctrine_migration_versions                                            |',
                '|                      | Column Name          | version                                                                |',
                '|----------------------------------------------------------------------------------------------------------------------|',
                '| Database             | Driver               | Doctrine\DBAL\Driver\PDO\SQLite\Driver                                 |',
                '|                      | Name                 | main                                                                   |',
                '|----------------------------------------------------------------------------------------------------------------------|',
                '| Versions             | Previous             | 1230                                                                   |',
                '|                      | Current              | 1233                                                                   |',
                '|                      | Next                 | Already at latest version                                              |',
                '|                      | Latest               | 1233                                                                   |',
                '|----------------------------------------------------------------------------------------------------------------------|',
                '| Migrations           | Executed             | 2                                                                      |',
                '|                      | Executed Unavailable | 2                                                                      |',
                '|                      | Available            | 0                                                                      |',
                '|                      | New                  | 0                                                                      |',
                '|----------------------------------------------------------------------------------------------------------------------|',
                sprintf('| Migration Namespaces | DoctrineMigrations   | %s |', str_pad(sys_get_temp_dir(), 70)),
                '+----------------------+----------------------+------------------------------------------------------------------------+',
            ],
            $lines
        );
    }
}
