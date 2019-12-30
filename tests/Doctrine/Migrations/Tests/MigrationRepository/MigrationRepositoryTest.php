<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\MigrationRepository;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\DuplicateMigrationVersion;
use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Tests\MigrationRepository\Migrations\A\A;
use Doctrine\Migrations\Tests\MigrationRepository\Migrations\A\B;
use Doctrine\Migrations\Tests\MigrationRepository\Migrations\B\C;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use function strcmp;

class MigrationRepositoryTest extends TestCase
{
    /** @var MigrationFactory|MockObject */
    private $versionFactory;

    /** @var MigrationRepository */
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
        $migrationRepository = new MigrationRepository(
            [A::class],
            [],
            new RecursiveRegexFinder('#.*\\.php$#i'),
            $this->versionFactory
        );

        $migrations = $migrationRepository->getMigrations();

        self::assertCount(1, $migrations);
        self::assertInstanceOf(A::class, $migrations->getMigration(new Version(A::class))->getMigration());
    }

    public function testNoMigrationsInFolder() : void
    {
        $migrationRepository = new MigrationRepository(
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

    public function testCustomMigrationSorting() : void
    {
        $migrationRepository = new MigrationRepository(
            [],
            [
                'Doctrine\Migrations\Tests\MigrationRepository\Migrations' => __DIR__ . '/Migrations',
            ],
            new RecursiveRegexFinder('#.*\\.php$#i'),
            $this->versionFactory,
            static function (AvailableMigration $m1, AvailableMigration $m2) {
                return strcmp((string) $m1->getVersion(), (string) $m2->getVersion())*-1;
            }
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
        $this->migrationRepository->registerMigrationInstance(new Version('Z'), $this->createMock(AbstractMigration::class));

        $migrations = $this->migrationRepository->getMigrations();

        self::assertCount(4, $migrations);
        self::assertSame('Z', (string) $migrations->getItems()[3]->getVersion());
    }

    public function testDuplicateLoadMigrationInstance() : void
    {
        $this->expectException(DuplicateMigrationVersion::class);
        $this->migrationRepository->registerMigrationInstance(new Version('Z'), $this->createMock(AbstractMigration::class));
        $this->migrationRepository->registerMigrationInstance(new Version('Z'), $this->createMock(AbstractMigration::class));
    }

    public function testFindMigrations() : void
    {
        $this->versionFactory
            ->expects(self::exactly(3))
            ->method('createVersion')
            ->willReturnCallback(function ($class) {
                return $this->createMock($class);
            });

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
            ->expects(self::any())
            ->method('createVersion')
            ->willReturnCallback(function ($class) {
                return $this->createMock($class);
            });

        $this->migrationRepository = new MigrationRepository(
            [],
            [
                'Doctrine\Migrations\Tests\MigrationRepository\Migrations' => __DIR__ . '/Migrations',
            ],
            new RecursiveRegexFinder('#.*\\.php$#i'),
            $this->versionFactory
        );
    }
}
