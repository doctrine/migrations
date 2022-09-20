<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\MigrationRepository;

use Closure;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\DuplicateMigrationVersion;
use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\FilesystemMigrationsRepository;
use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Tests\Helper;
use Doctrine\Migrations\Tests\MigrationRepository\Migrations\A\A;
use Doctrine\Migrations\Tests\MigrationRepository\Migrations\A\B;
use Doctrine\Migrations\Tests\MigrationRepository\Migrations\B\C;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FilesystemMigrationsRepositoryTest extends TestCase
{
    /** @var MigrationFactory&MockObject */
    private MigrationFactory $versionFactory;

    private MigrationsRepository $migrationRepository;

    public function testCheckNonExistentMigration(): void
    {
        self::assertFalse($this->migrationRepository->hasMigration('non_existent'));
    }

    public function testGetNonExistentMigration(): void
    {
        $this->expectException(MigrationClassNotFound::class);

        $this->migrationRepository->getMigration(new Version('non_existent'));
    }

    public function testGetOneMigration(): void
    {
        $migration = $this->migrationRepository->getMigration(new Version(A::class));

        self::assertSame(A::class, (string) $migration->getVersion());
        self::assertInstanceOf(A::class, $migration->getMigration());
    }

    public function testLoadMigrationClassesProvidedViaConstructor(): void
    {
        $migrationRepository = new FilesystemMigrationsRepository(
            [A::class],
            [],
            new RecursiveRegexFinder('#.*\\.php$#i'),
            $this->versionFactory
        );

        $migrations = $migrationRepository->getMigrations();

        self::assertCount(1, $migrations);
        self::assertInstanceOf(A::class, $migrations->getMigration(new Version(A::class))->getMigration());
    }

    public function testNoMigrationsInFolder(): void
    {
        $migrationRepository = new FilesystemMigrationsRepository(
            [],
            [
                'Doctrine\Migrations\Tests\MigrationRepository\Migrations' => __DIR__ . '/NoMigrations',
            ],
            new RecursiveRegexFinder('#.*\\.php$#i'),
            $this->versionFactory
        );

        $migrations = $migrationRepository->getMigrations();

        self::assertCount(0, $migrations);
    }

    public function testLoadMigrationInstance(): void
    {
        Helper::registerMigrationInstance($this->migrationRepository, new Version('Z'), $this->createMock(AbstractMigration::class));

        $migrations = $this->migrationRepository->getMigrations();

        self::assertCount(4, $migrations);

        $migration = $this->migrationRepository->getMigration(new Version('Z'));

        self::assertSame('Z', (string) $migration->getVersion());
    }

    public function testDuplicateLoadMigrationInstance(): void
    {
        $this->expectException(DuplicateMigrationVersion::class);
        Helper::registerMigrationInstance($this->migrationRepository, new Version('Z'), $this->createMock(AbstractMigration::class));
        Helper::registerMigrationInstance($this->migrationRepository, new Version('Z'), $this->createMock(AbstractMigration::class));
    }

    public function testFindMigrations(): void
    {
        $this->versionFactory
            ->expects(self::exactly(3))
            ->method('createVersion');

        $migrations = $this->migrationRepository->getMigrations();

        self::assertCount(3, $migrations);

        self::assertTrue($migrations->hasMigration(new Version(A::class)));
        self::assertTrue($migrations->hasMigration(new Version(B::class)));
        self::assertTrue($migrations->hasMigration(new Version(C::class)));
        self::assertFalse($migrations->hasMigration(new Version('Z')));
    }

    protected function setUp(): void
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
            $this->versionFactory
        );
    }
}
