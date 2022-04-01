<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Tests\Helper;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tests\TestLogger;
use Doctrine\Migrations\Tools\Console\Command\VersionCommand;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use InvalidArgumentException;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

use function sys_get_temp_dir;

class MigrationVersionTest extends MigrationTestCase
{
    private VersionCommand $command;

    private MigrationsRepository $migrationRepository;

    private MetadataStorage $metadataStorage;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $configuration = new Configuration();
        $configuration->addMigrationsDirectory('DoctrineMigrations', sys_get_temp_dir());

        $conn   = $this->getSqliteConnection();
        $logger = new TestLogger();

        $dependencyFactory = DependencyFactory::fromConnection(
            new ExistingConfiguration($configuration),
            new ExistingConnection($conn),
            $logger
        );

        $this->migrationRepository = $dependencyFactory->getMigrationRepository();
        $this->metadataStorage     = $dependencyFactory->getMetadataStorage();
        $this->metadataStorage->ensureInitialized();

        $this->command       = new VersionCommand($dependencyFactory);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test "--add --range-to --range-from" options on migrate only versions in interval.
     */
    public function testAddRangeOption(): void
    {
        $mock = $this->createMock(AbstractMigration::class);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1233'), $mock);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1234'), $mock);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1235'), $mock);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1239'), $mock);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1240'), $mock);

        $this->commandTester->execute(
            [
                '--add'        => true,
                '--range-from' => '1234',
                '--range-to'   => '1239',
            ],
            ['interactive' => false]
        );

        $executed = $this->metadataStorage->getExecutedMigrations();
        self::assertFalse($executed->hasMigration(new Version('1233')));
        self::assertTrue($executed->hasMigration(new Version('1234')));
        self::assertTrue($executed->hasMigration(new Version('1235')));
        self::assertTrue($executed->hasMigration(new Version('1239')));
        self::assertFalse($executed->hasMigration(new Version('1240')));
    }

    /**
     * Test "--add --range-from" options without "--range-to".
     */
    public function testAddRangeWithoutRangeToOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options --range-to and --range-from should be used together.');

        $this->commandTester->execute(
            [
                '--add'        => true,
                '--range-from' => '1233',
            ],
            ['interactive' => false]
        );
    }

    /**
     * Test "--add --range-to" options without "--range-from".
     */
    public function testAddRangeWithoutRangeFromOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options --range-to and --range-from should be used together.');

        $this->commandTester->execute(
            [
                '--add'      => true,
                '--range-to' => '1233',
            ],
            ['interactive' => false]
        );
    }

    /**
     * Test "--add --all --range-to" options.
     */
    public function testAddAllOptionsWithRangeTo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options --all and --range-to/--range-from both used. You should use only one of them.');

        $this->commandTester->execute(
            [
                '--add'      => true,
                '--all'      => true,
                '--range-to' => '1233',
            ],
            ['interactive' => false]
        );
    }

    /**
     * Test "--add --all --range-from" options.
     */
    public function testAddAllOptionsWithRangeFrom(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options --all and --range-to/--range-from both used. You should use only one of them.');

        $this->commandTester->execute(
            [
                '--add'      => true,
                '--all'      => true,
                '--range-from' => '1233',
            ],
            ['interactive' => false]
        );
    }

    /**
     * Test "--delete --range-to --range-from" options on migrate down only versions in interval.
     */
    public function testDeleteRangeOption(): void
    {
        $mock = $this->createMock(AbstractMigration::class);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1233'), $mock);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1234'), $mock);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1235'), $mock);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1239'), $mock);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1240'), $mock);

        foreach (['1233', '1234', '1239', '1240'] as $v) {
            $r = new ExecutionResult(new Version($v));
            $this->metadataStorage->complete($r);
        }

        $this->commandTester->execute(
            [
                '--delete'     => true,
                '--range-from' => '1234',
                '--range-to'   => '1239',
            ],
            ['interactive' => false]
        );

        $executed = $this->metadataStorage->getExecutedMigrations();
        self::assertTrue($executed->hasMigration(new Version('1233')));
        self::assertFalse($executed->hasMigration(new Version('1234')));
        self::assertFalse($executed->hasMigration(new Version('1235')));
        self::assertFalse($executed->hasMigration(new Version('1239')));
        self::assertTrue($executed->hasMigration(new Version('1240')));
    }

    /**
     * Test "--add --all" options on migrate all versions.
     */
    public function testAddAllOption(): void
    {
        $migrationClass = $this->createMock(AbstractMigration::class);

        Helper::registerMigrationInstance($this->migrationRepository, new Version('1231'), $migrationClass);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1232'), $migrationClass);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1234'), $migrationClass);

        $result = new ExecutionResult(new Version('1234'), Direction::UP);
        $this->metadataStorage->complete($result);

        $this->commandTester->execute(
            [
                '--add'   => true,
                '--all'   => true,
            ],
            ['interactive' => false]
        );

        $executedMigrations = $this->metadataStorage->getExecutedMigrations();

        self::assertTrue($executedMigrations->hasMigration(new Version('1231')));
        self::assertTrue($executedMigrations->hasMigration(new Version('1232')));
        self::assertTrue($executedMigrations->hasMigration(new Version('1234')));
    }

    /**
     * Test "--delete --all" options on migrate down all versions.
     */
    public function testDeleteAllOption(): void
    {
        $migrationClass = $this->createMock(AbstractMigration::class);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1233'), $migrationClass);

        $result = new ExecutionResult(new Version('1233'), Direction::UP);
        $this->metadataStorage->complete($result);

        $result = new ExecutionResult(new Version('1234'), Direction::UP);
        $this->metadataStorage->complete($result);

        $this->command->setHelperSet(new HelperSet([new QuestionHelper()]));
        $this->commandTester->execute(
            [
                '--delete' => true,
                '--all'    => true,
            ],
            ['interactive' => false]
        );

        $executedMigrations = $this->metadataStorage->getExecutedMigrations();

        self::assertFalse($executedMigrations->hasMigration(new Version('1233')));
        self::assertFalse($executedMigrations->hasMigration(new Version('1234')));
    }

    /**
     * Test "--add" option on migrate one version.
     */
    public function testAddOption(): void
    {
        $migrationClass = $this->createMock(AbstractMigration::class);

        Helper::registerMigrationInstance($this->migrationRepository, new Version('1232'), $migrationClass);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1234'), $migrationClass);

        $this->commandTester->execute(
            [
                '--add'   => true,
                'version' => '1234',
            ],
            ['interactive' => false]
        );

        $executedMigrations = $this->metadataStorage->getExecutedMigrations();

        self::assertFalse($executedMigrations->hasMigration(new Version('1232')));
        self::assertTrue($executedMigrations->hasMigration(new Version('1234')));
    }

    /**
     * Test "--delete" options on migrate down one version.
     */
    public function testDeleteOption(): void
    {
        $migrationClass = $this->createMock(AbstractMigration::class);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1233'), $migrationClass);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1234'), $migrationClass);

        $result = new ExecutionResult(new Version('1233'), Direction::UP);
        $this->metadataStorage->complete($result);

        $result = new ExecutionResult(new Version('1234'), Direction::UP);
        $this->metadataStorage->complete($result);

        $this->commandTester->execute(
            [
                '--delete' => true,
                'version'  => '1234',
            ],
            ['interactive' => false]
        );

        $executedMigrations = $this->metadataStorage->getExecutedMigrations();

        self::assertFalse($executedMigrations->hasMigration(new Version('1234')));
        self::assertTrue($executedMigrations->hasMigration(new Version('1233')));
    }

    /**
     * Test "--add" option on migrate already migrated version.
     */
    public function testAddOptionIfVersionAlreadyMigrated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The version "1233" already exists in the version table.');

        $migrationClass = $this->createMock(AbstractMigration::class);

        Helper::registerMigrationInstance($this->migrationRepository, new Version('1233'), $migrationClass);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1234'), $migrationClass);

        $result = new ExecutionResult(new Version('1233'), Direction::UP);
        $this->metadataStorage->complete($result);

        $this->commandTester->execute(
            [
                '--add'   => true,
                'version' => '1233',
            ],
            ['interactive' => false]
        );
    }

    /**
     * Test "--delete" option on not migrated version.
     */
    public function testDeleteOptionIfVersionNotMigrated(): void
    {
        $migrationClass = $this->createMock(AbstractMigration::class);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1233'), $migrationClass);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The version "1233" does not exist in the version table.');

        $this->commandTester->execute(
            [
                '--delete' => true,
                'version'  => '1233',
            ],
            ['interactive' => false]
        );
    }

    /**
     * Test "--delete" option on migrated version without existing version file.
     */
    public function testDeleteOptionIfVersionFileDoesNotExist(): void
    {
        $result = new ExecutionResult(new Version('1233'), Direction::UP);
        $this->metadataStorage->complete($result);

        $this->command->setHelperSet(new HelperSet([new QuestionHelper()]));

        $this->commandTester->execute(
            [
                '--delete' => true,
                'version'  => '1233',
            ],
            ['interactive' => false]
        );

        self::assertStringContainsString('1233 deleted from the version table.', $this->commandTester->getDisplay(true));
    }
}
