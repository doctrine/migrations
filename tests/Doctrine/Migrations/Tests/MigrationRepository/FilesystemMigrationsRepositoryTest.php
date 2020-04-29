<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\MigrationRepository;

use Closure;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\DuplicateMigrationVersion;
use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\FilesystemMigrationsRepository;
use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\Migrations\Tests\Helper;
use Doctrine\Migrations\Tests\MigrationRepository\Migrations\A\A;
use Doctrine\Migrations\Tests\MigrationRepository\Migrations\A\B;
use Doctrine\Migrations\Tests\MigrationRepository\Migrations\B\C;
use Doctrine\Migrations\Version\AlphabeticalComparator;
use Doctrine\Migrations\Version\Comparator;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use function strcmp;

class FilesystemMigrationsRepositoryTest extends TestCase
{
    /** @var MigrationFactory|MockObject */
    private $versionFactory;

    /** @var FilesystemMigrationsRepository */
    private $migrationRepository;

    public function testCheckNonExistentMigration() : void
    {
        self::assertFalse($this->migrationRepository->hasMigration('non_existent'));
    }

    public function testGetNonExistentMigration() : void
    {
        $this->expectException(MigrationClassNotFound::class);

        $this->migrationRepository->getMigration(new Version('non_existent'));
    }

    public function testGetOneMigration() : void
    {
        $migration = $this->migrationRepository->getMigration(new Version(A::class));

        self::assertSame(A::class, (string) $migration->getVersion());
        self::assertInstanceOf(A::class, $migration->getMigration());
    }

    public function testLoadMigrationClassesProvidedViaConstructor() : void
    {
        $migrationRepository = new FilesystemMigrationsRepository(
            [A::class],
            [],
            new RecursiveRegexFinder('#.*\\.php$#i'),
            $this->versionFactory,
            new AlphabeticalComparator()
        );

        $migrations = $migrationRepository->getMigrations();

        self::assertCount(1, $migrations);
        self::assertInstanceOf(A::class, $migrations->getMigration(new Version(A::class))->getMigration());
    }

    public function testNoMigrationsInFolder() : void
    {
        $migrationRepository = new FilesystemMigrationsRepository(
            [],
            [
                'Doctrine\Migrations\Tests\MigrationRepository\Migrations' => __DIR__ . '/NoMigrations',
            ],
            new RecursiveRegexFinder('#.*\\.php$#i'),
            $this->versionFactory,
            new AlphabeticalComparator()
        );

        $migrations = $migrationRepository->getMigrations();

        self::assertCount(0, $migrations);
    }

    public function testCustomMigrationSorting() : void
    {
        $reverseSorter       = new class implements Comparator {
            public function compare(Version $a, Version $b) : int
            {
                return strcmp((string) $b, (string) $a);
            }
        };
        $migrationRepository = new FilesystemMigrationsRepository(
            [],
            [
                'Doctrine\Migrations\Tests\MigrationRepository\Migrations' => __DIR__ . '/Migrations',
            ],
            new RecursiveRegexFinder('#.*\\.php$#i'),
            $this->versionFactory,
            $reverseSorter
        );

        $migrations = $migrationRepository->getMigrations();

        self::assertCount(3, $migrations);

        // reverse order
        self::assertSame(A::class, (string) $migrations->getItems()[2]->getVersion());
        self::assertSame(B::class, (string) $migrations->getItems()[1]->getVersion());
        self::assertSame(C::class, (string) $migrations->getItems()[0]->getVersion());
    }

    public function testLoadMigrationInstance() : void
    {
        Helper::registerMigrationInstance($this->migrationRepository, new Version('Z'), $this->createMock(AbstractMigration::class));

        $migrations = $this->migrationRepository->getMigrations();

        self::assertCount(4, $migrations);
        self::assertSame('Z', (string) $migrations->getItems()[3]->getVersion());
    }

    public function testDuplicateLoadMigrationInstance() : void
    {
        $this->expectException(DuplicateMigrationVersion::class);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('Z'), $this->createMock(AbstractMigration::class));
        Helper::registerMigrationInstance($this->migrationRepository, new Version('Z'), $this->createMock(AbstractMigration::class));
    }

    public function testFindMigrations() : void
    {
        $this->versionFactory
            ->expects(self::exactly(3))
            ->method('createVersion');

        $migrations = $this->migrationRepository->getMigrations();

        self::assertCount(3, $migrations);

        self::assertSame(A::class, (string) $migrations->getItems()[0]->getVersion());
        self::assertSame(B::class, (string) $migrations->getItems()[1]->getVersion());
        self::assertSame(C::class, (string) $migrations->getItems()[2]->getVersion());

        self::assertInstanceOf(A::class, $migrations->getItems()[0]->getMigration());
        self::assertInstanceOf(B::class, $migrations->getItems()[1]->getMigration());
        self::assertInstanceOf(C::class, $migrations->getItems()[2]->getMigration());
    }

    protected function setUp() : void
    {
        $this->versionFactory = $this->createMock(MigrationFactory::class);
        $this->versionFactory
            ->method('createVersion')
            ->willReturnCallback(Closure::fromCallable([$this, 'createStub']));

        $this->migrationRepository = new FilesystemMigrationsRepository(
            [],
            [
                'Doctrine\Migrations\Tests\MigrationRepository\Migrations' => __DIR__ . '/Migrations',
            ],
            new RecursiveRegexFinder('#.*\\.php$#i'),
            $this->versionFactory,
            new AlphabeticalComparator()
        );
    }
}
