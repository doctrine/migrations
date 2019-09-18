<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Finder\Finder;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tests\Stub\Version1Test;
use Doctrine\Migrations\Tests\TestLogger;
use Doctrine\Migrations\Tools\Console\Command\VersionCommand;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Factory;
use Doctrine\Migrations\Version\Version;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;
use function sys_get_temp_dir;

class MigrationVersionTest extends MigrationTestCase
{
    /** @var VersionCommand|MockObject */
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

    protected function setUp() : void
    {
        $configuration = new Configuration();
        $configuration->addMigrationsDirectory('DoctrineMigrations', sys_get_temp_dir());

        $conn = $this->getSqliteConnection();
        $logger = new TestLogger();
        
        $dependencyFactory = new DependencyFactory($configuration, $conn, $logger);

        $this->migrationRepository = $dependencyFactory->getMigrationRepository();
        $this->metadataStorage = $dependencyFactory->getMetadataStorage();

        $this->command = new VersionCommand(null, $dependencyFactory);
        $this->commandTester = new CommandTester($this->command);


    }

    /**
     * Test "--add --range-to --range-from" options on migrate only versions in interval.
     */
    public function testAddRangeOption() : void
    {
        $this->markTestSkipped();
        $this->configuration->registerMigration('1233', Version1Test::class);
        $this->configuration->registerMigration('1234', Version1Test::class);
        $this->configuration->registerMigration('1235', Version1Test::class);
        $this->configuration->registerMigration('1239', Version1Test::class);
        $this->configuration->registerMigration('1240', Version1Test::class);

        
        $this->commandTester->execute(
            [
                '--add'        => true,
                '--range-from' => '1234',
                '--range-to'   => '1239',
            ],
            ['interactive' => false]
        );

        self::assertFalse($this->configuration->getVersion('1233')->isMigrated());
        self::assertTrue($this->configuration->getVersion('1234')->isMigrated());
        self::assertTrue($this->configuration->getVersion('1235')->isMigrated());
        self::assertTrue($this->configuration->getVersion('1239')->isMigrated());
        self::assertFalse($this->configuration->getVersion('1240')->isMigrated());
    }

    /**
     * Test "--add --range-from" options without "--range-to".
     */
    public function testAddRangeWithoutRangeToOption() : void
    {
        $this->markTestSkipped();

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
    public function testAddRangeWithoutRangeFromOption() : void
    {
        $this->markTestSkipped();

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
    public function testAddAllOptionsWithRangeTo() : void
    {
        $this->markTestSkipped();

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
    public function testAddAllOptionsWithRangeFrom() : void
    {
        $this->markTestSkipped();

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
    public function testDeleteRangeOption() : void
    {
        $this->markTestSkipped();
        $this->configuration->registerMigration('1233', Version1Test::class);
        $this->configuration->registerMigration('1234', Version1Test::class);
        $this->configuration->registerMigration('1235', Version1Test::class);
        $this->configuration->registerMigration('1239', Version1Test::class);
        $this->configuration->registerMigration('1240', Version1Test::class);

        $this->configuration->getVersion('1233')->markMigrated();
        $this->configuration->getVersion('1234')->markMigrated();
        $this->configuration->getVersion('1239')->markMigrated();
        $this->configuration->getVersion('1240')->markMigrated();

        
        $this->commandTester->execute(
            [
                '--delete'     => true,
                '--range-from' => '1234',
                '--range-to'   => '1239',
            ],
            ['interactive' => false]
        );

        self::assertTrue($this->configuration->getVersion('1233')->isMigrated());
        self::assertFalse($this->configuration->getVersion('1234')->isMigrated());
        self::assertFalse($this->configuration->getVersion('1235')->isMigrated());
        self::assertFalse($this->configuration->getVersion('1239')->isMigrated());
        self::assertTrue($this->configuration->getVersion('1240')->isMigrated());
    }

    /**
     * Test "--add --all" options on migrate all versions.
     */
    public function testAddAllOption() : void
    {
        $migrationClass = $this->createMock(AbstractMigration::class);

        $this->migrationRepository->registerMigrationInstance(new Version('1231'), $migrationClass);
        $this->migrationRepository->registerMigrationInstance(new Version('1232'), $migrationClass);
        $this->migrationRepository->registerMigrationInstance(new Version('1234'), $migrationClass);

        $result = new ExecutionResult(new Version('1234'), Direction::UP);
        $this->metadataStorage->complete($result);
        
        $this->commandTester->execute(
            [
                '--add'   => true,
                '--all'   => true
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
    public function testDeleteAllOption() : void
    {
        $migrationClass = $this->createMock(AbstractMigration::class);
        $this->migrationRepository->registerMigrationInstance(new Version('1233'), $migrationClass);

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
    public function testAddOption() : void
    {
        $migrationClass = $this->createMock(AbstractMigration::class);

        $this->migrationRepository->registerMigrationInstance(new Version('1232'), $migrationClass);
        $this->migrationRepository->registerMigrationInstance(new Version('1234'), $migrationClass);

        
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
    public function testDeleteOption() : void
    {
        $migrationClass = $this->createMock(AbstractMigration::class);
        $this->migrationRepository->registerMigrationInstance(new Version('1233'), $migrationClass);
        $this->migrationRepository->registerMigrationInstance(new Version('1234'), $migrationClass);

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
    public function testAddOptionIfVersionAlreadyMigrated() : void
    {

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The version "1233" already exists in the version table.');


        $migrationClass = $this->createMock(AbstractMigration::class);

        $this->migrationRepository->registerMigrationInstance(new Version('1233'), $migrationClass);
        $this->migrationRepository->registerMigrationInstance(new Version('1234'), $migrationClass);

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
    public function testDeleteOptionIfVersionNotMigrated() : void
    {
        $migrationClass = $this->createMock(AbstractMigration::class);
        $this->migrationRepository->registerMigrationInstance(new Version('1233'), $migrationClass);

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
    public function testDeleteOptionIfVersionFileDoesNotExist() : void
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

        self::assertContains('1233 deleted from the version table.', $this->commandTester->getDisplay());
    }
}
