<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Tests\Helper;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Command\VersionCommand;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\TestWith;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Completion\Suggestion;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

use function array_map;
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

        $conn = $this->getSqliteConnection();

        $dependencyFactory = DependencyFactory::fromConnection(
            new ExistingConfiguration($configuration),
            new ExistingConnection($conn),
            new NullLogger(),
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
        $mock = self::createStub(AbstractMigration::class);
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
            ['interactive' => false],
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
            ['interactive' => false],
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
            ['interactive' => false],
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
            ['interactive' => false],
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
            ['interactive' => false],
        );
    }

    /**
     * Test "--delete --range-to --range-from" options on migrate down only versions in interval.
     */
    public function testDeleteRangeOption(): void
    {
        $mock = self::createStub(AbstractMigration::class);
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
            ['interactive' => false],
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
        $migrationClass = self::createStub(AbstractMigration::class);

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
            ['interactive' => false],
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
        $migrationClass = self::createStub(AbstractMigration::class);
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
            ['interactive' => false],
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
        $migrationClass = self::createStub(AbstractMigration::class);

        Helper::registerMigrationInstance($this->migrationRepository, new Version('1232'), $migrationClass);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1234'), $migrationClass);

        $this->commandTester->execute(
            [
                '--add'   => true,
                'version' => '1234',
            ],
            ['interactive' => false],
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
        $migrationClass = self::createStub(AbstractMigration::class);
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
            ['interactive' => false],
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

        $migrationClass = self::createStub(AbstractMigration::class);

        Helper::registerMigrationInstance($this->migrationRepository, new Version('1233'), $migrationClass);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1234'), $migrationClass);

        $result = new ExecutionResult(new Version('1233'), Direction::UP);
        $this->metadataStorage->complete($result);

        $this->commandTester->execute(
            [
                '--add'   => true,
                'version' => '1233',
            ],
            ['interactive' => false],
        );
    }

    /**
     * Test "--delete" option on not migrated version.
     */
    public function testDeleteOptionIfVersionNotMigrated(): void
    {
        $migrationClass = self::createStub(AbstractMigration::class);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1233'), $migrationClass);

        $exitCode = $this->commandTester->execute(
            [
                '--delete' => true,
                'version'  => '1233',
            ],
            ['interactive' => false],
        );

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('1233 is not in the version table, nothing to delete.', $this->commandTester->getDisplay());
        self::assertStringNotContainsString('deleted from the version table.', $this->commandTester->getDisplay());
        self::assertFalse($this->metadataStorage->getExecutedMigrations()->hasMigration(new Version('1233')));
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
            ['interactive' => false],
        );

        self::assertStringContainsString('1233 deleted from the version table.', $this->commandTester->getDisplay(true));
    }

    /**
     * @param list<string> $args
     * @param list<string> $expectedSuggestions
     */
    #[TestWith([['console'], ['1233', '1234']])]
    #[TestWith([['console', '1233'], ['1233', '1234']])]
    public function testComplete(array $args, array $expectedSuggestions): void
    {
        $migration = self::createStub(AbstractMigration::class);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1233'), $migration);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1234'), $migration);

        $input = CompletionInput::fromTokens($args, 1);
        $input->bind($this->command->getDefinition());
        $suggestions = new CompletionSuggestions();

        $this->command->complete($input, $suggestions);

        self::assertSame(
            $expectedSuggestions,
            array_map(
                static fn (Suggestion $suggestion): string => $suggestion->getValue(),
                $suggestions->getValueSuggestions(),
            ),
        );
    }

    /**
     * Test "--delete" option on not migrated version without existing version file.
     */
    public function testDeleteOptionIfVersionNotMigratedAndFileDoesNotExist(): void
    {
        $exitCode = $this->commandTester->execute(
            [
                '--delete' => true,
                'version'  => '1233',
            ],
            ['interactive' => false],
        );
        self::assertSame(0, $exitCode);
        self::assertStringContainsString('1233 is not in the version table, nothing to delete.', $this->commandTester->getDisplay());
        self::assertStringNotContainsString('deleted from the version table.', $this->commandTester->getDisplay());
        self::assertFalse($this->metadataStorage->getExecutedMigrations()->hasMigration(new Version('1233')));
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testDeleteOptionIsIdempotent(bool $classExists): void
    {
        $version = new Version('1233');
        if ($classExists) {
            Helper::registerMigrationInstance($this->migrationRepository, $version, self::createStub(AbstractMigration::class));
        }

        $this->metadataStorage->complete(new ExecutionResult($version, Direction::UP));

        $arguments = ['--delete' => true, 'version' => '1233'];
        $options   = ['interactive' => false];

        self::assertSame(0, $this->commandTester->execute($arguments, $options));
        self::assertStringContainsString('1233 deleted from the version table.', $this->commandTester->getDisplay());
        self::assertFalse($this->metadataStorage->getExecutedMigrations()->hasMigration($version));

        self::assertSame(0, $this->commandTester->execute($arguments, $options));
        self::assertStringContainsString('1233 is not in the version table, nothing to delete.', $this->commandTester->getDisplay());
        self::assertStringNotContainsString('deleted from the version table.', $this->commandTester->getDisplay());
        self::assertFalse($this->metadataStorage->getExecutedMigrations()->hasMigration($version));
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testDeleteMissingVersionDoesNotWriteToCustomStorage(bool $classExists): void
    {
        $storage = self::createMock(MetadataStorage::class);
        $storage->method('getExecutedMigrations')->willReturn(new ExecutedMigrationsList([]));
        $storage->expects(self::never())->method('complete');
        $storage->expects(self::never())->method('reset');

        $factory = DependencyFactory::fromConnection(
            new ExistingConfiguration(new Configuration()),
            new ExistingConnection($this->getSqliteConnection()),
        );
        $factory->setService(MetadataStorage::class, $storage);
        if ($classExists) {
            Helper::registerMigrationInstance($factory->getMigrationRepository(), new Version('1233'), self::createStub(AbstractMigration::class));
        }

        $tester = new CommandTester(new VersionCommand($factory));
        self::assertSame(0, $tester->execute(['--delete' => true, 'version' => '1233'], ['interactive' => false]));
        self::assertStringContainsString('1233 is not in the version table, nothing to delete.', $tester->getDisplay());
    }

    /** @param array<string, bool|string> $selection */
    #[TestWith([['--all' => true]])]
    #[TestWith([['--range-from' => '1233', '--range-to' => '1234']])]
    public function testDeleteUnexecutedVersionsInBatchRemainsSilent(array $selection): void
    {
        $migration = self::createStub(AbstractMigration::class);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1233'), $migration);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1234'), $migration);

        self::assertSame(0, $this->commandTester->execute(['--delete' => true] + $selection, ['interactive' => false]));
        self::assertSame('', $this->commandTester->getDisplay());
        self::assertCount(0, $this->metadataStorage->getExecutedMigrations());
    }

    #[TestWith([false, 'yes'])]
    #[TestWith([true, 'yes'])]
    #[TestWith([false, 'no'])]
    #[TestWith([true, 'no'])]
    public function testDeleteMissingVersionInteractively(bool $classExists, string $answer): void
    {
        if ($classExists) {
            Helper::registerMigrationInstance($this->migrationRepository, new Version('1233'), self::createStub(AbstractMigration::class));
        }

        $this->commandTester->setInputs([$answer]);
        self::assertSame(0, $this->commandTester->execute(['--delete' => true, 'version' => '1233']));
        if ($answer === 'yes') {
            self::assertStringContainsString('1233 is not in the version table, nothing to delete.', $this->commandTester->getDisplay());
        } else {
            self::assertStringContainsString('Migration cancelled!', $this->commandTester->getDisplay());
            self::assertStringNotContainsString('nothing to delete', $this->commandTester->getDisplay());
        }

        self::assertFalse($this->metadataStorage->getExecutedMigrations()->hasMigration(new Version('1233')));
    }

    public function testDeleteMissingVersionThroughCommandAlias(): void
    {
        Helper::registerMigrationInstance($this->migrationRepository, new Version('1233'), self::createStub(AbstractMigration::class));
        $application = new Application();
        $application->addCommands([$this->command]);
        $tester = new CommandTester($application->find('version'));

        self::assertSame(0, $tester->execute(['--delete' => true, 'version' => '1233'], ['interactive' => false]));
        self::assertStringContainsString('1233 is not in the version table, nothing to delete.', $tester->getDisplay());
    }

    public function testAddStillTakesPrecedenceOverDeleteWithAvailableClass(): void
    {
        $version = new Version('1233');
        Helper::registerMigrationInstance($this->migrationRepository, $version, self::createStub(AbstractMigration::class));

        self::assertSame(0, $this->commandTester->execute(['--add' => true, '--delete' => true, 'version' => '1233'], ['interactive' => false]));
        self::assertTrue($this->metadataStorage->getExecutedMigrations()->hasMigration($version));
        self::assertStringContainsString('1233 added to the version table.', $this->commandTester->getDisplay());
    }
}
