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
use function strlen;
use function sys_get_temp_dir;
use function trim;

class StatusCommandTest extends MigrationTestCase
{
    /** @var StatusCommand */
    private $command;

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

        $dependencyFactory = DependencyFactory::fromConnection(
            new ExistingConfiguration($configuration),
            new ExistingConnection($conn)
        );

        $this->metadataStorage = $dependencyFactory->getMetadataStorage();

        $this->metadataStorage->ensureInitialized();

        $this->command       = new StatusCommand($dependencyFactory);
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecute() : void
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

        $tempDir = sys_get_temp_dir();
        $tempDir = str_pad($tempDir, 74-strlen($tempDir));

        self::assertSame(
            [
                0 => '+----------------------+----------------------+------------------------------------------------------------------------+',
                1 => '| Configuration                                                                                                        |',
                2 => '+----------------------+----------------------+------------------------------------------------------------------------+',
                3 => '| Project              | Doctrine Database Migrations                                                                  |',
                4 => '|----------------------------------------------------------------------------------------------------------------------|',
                5 => '| Project              | Doctrine Database Migrations                                                                  |',
                6 => '|----------------------------------------------------------------------------------------------------------------------|',
                7 => '| Storage              | Type                 | Doctrine\\Migrations\\Metadata\\Storage\\TableMetadataStorageConfiguration |',
                8 => '|                      | Table Name           | doctrine_migration_versions                                            |',
                9 => '|                      | Column Name          | version                                                                |',
                10 => '|----------------------------------------------------------------------------------------------------------------------|',
                11 => '| Database             | Driver               | pdo_sqlite                                                             |',
                12 => '|                      | Host                 |                                                                        |',
                13 => '|                      | Name                 |                                                                        |',
                14 => '|----------------------------------------------------------------------------------------------------------------------|',
                15 => '| Versions             | Previous             | 1230                                                                   |',
                16 => '|                      | Current              | 1233                                                                   |',
                17 => '|                      | Next                 | Already at latest version                                              |',
                18 => '|                      | Latest               |                                                                        |',
                19 => '|----------------------------------------------------------------------------------------------------------------------|',
                20 => '| Migrations           | Executed             | 2                                                                      |',
                21 => '|                      | Executed Unavailable | 2                                                                      |',
                22 => '|                      | Available            | 0                                                                      |',
                23 => '|                      | New                  | 0                                                                      |',
                24 => '|----------------------------------------------------------------------------------------------------------------------|',
                25 => sprintf('| Migration Namespaces | DoctrineMigrations   | %s |', str_pad(sys_get_temp_dir(), 70)),
                26 => '+----------------------+----------------------+------------------------------------------------------------------------+',
            ],
            $lines
        );
    }
}
