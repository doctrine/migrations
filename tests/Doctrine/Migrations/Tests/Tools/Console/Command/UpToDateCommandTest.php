<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Command\UpToDateCommand;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use Symfony\Component\Console\Tester\CommandTester;
use function sys_get_temp_dir;

class UpToDateCommandTest extends MigrationTestCase
{
    /** @var MigrationRepository */
    private $migrationRepository;

    /** @var MetadataStorage */
    private $metadataStorage;

    /** @var CommandTester */
    private $commandTester;

    /** @var UpToDateCommand */
    private $command;

    /** @var Connection */
    private $conn;

    /** @var TableMetadataStorageConfiguration */
    private $metadataConfig;

    protected function setUp() : void
    {
        $this->metadataConfig = new TableMetadataStorageConfiguration();

        $configuration = new Configuration();
        $configuration->setMetadataStorageConfiguration($this->metadataConfig);
        $configuration->addMigrationsDirectory('DoctrineMigrations', sys_get_temp_dir());

        $this->conn = $this->getSqliteConnection();

        $dependencyFactory = DependencyFactory::fromConnection(new Configuration\ExistingConfiguration($configuration), new ExistingConnection($this->conn));

        $this->migrationRepository = $dependencyFactory->getMigrationRepository();
        $this->metadataStorage     = $dependencyFactory->getMetadataStorage();
        $this->metadataStorage->ensureInitialized();

        $this->command       = new UpToDateCommand(null, $dependencyFactory);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * @param string[] $migrations
     * @param string[] $migratedVersions
     *
     * @throws MigrationException
     *
     * @dataProvider dataIsUpToDate
     */
    public function testIsUpToDate(array $migrations, array $migratedVersions, int $exitCode, bool $failOnUnregistered = false) : void
    {
        $migrationClass = $this->createMock(AbstractMigration::class);
        foreach ($migrations as $version) {
            $this->migrationRepository->registerMigrationInstance(new Version($version), $migrationClass);
        }

        foreach ($migratedVersions as $version) {
            $result = new ExecutionResult(new Version($version), Direction::UP);
            $this->metadataStorage->complete($result);
        }

        $this->commandTester->execute(['--fail-on-unregistered' => $failOnUnregistered]);

        self::assertSame($exitCode, $this->commandTester->getStatusCode());
    }

    public function testNoMetadataStorage() : void
    {
        $this->conn->getSchemaManager()->dropTable($this->metadataConfig->getTableName());

        $this->commandTester->execute([]);

        self::assertStringContainsString(
            'The metadata storage is not initialized, please run the sync-metadata-storage command to fix this issue.',
            $this->commandTester->getDisplay()
        );
        self::assertSame(3, $this->commandTester->getStatusCode());
    }

    /**
     * @return mixed[][]
     */
    public function dataIsUpToDate() : array
    {
        return [
            'up-to-date' => [
                ['20160614015627'],
                ['20160614015627'],
                0,
            ],
            'empty-migration-set' => [
                [],
                [],
                0,
            ],
            'one-migration-available' => [
                ['20150614015627'],
                [],
                1,
            ],
            'many-migrations-available' => [
                [
                    '20110614015627',
                    '20120614015627',
                    '20130614015627',
                    '20140614015627',
                ],
                ['20110614015627'],
                1,
            ],
            'unregistered-migrations' => [
                [],
                ['20160614015627', '20120614015627'],
                0,
            ],
            'unregistered-migrations-fail' => [
                [],
                ['20160614015627', '20120614015627'],
                2,
                true,
            ],
        ];
    }
}
