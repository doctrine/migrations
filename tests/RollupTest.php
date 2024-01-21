<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\RollupFailed;
use Doctrine\Migrations\FilesystemMigrationsRepository;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsSet;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Rollup;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RollupTest extends TestCase
{
    /** @var AbstractMigration&MockObject */
    private AbstractMigration $abstractMigration;

    /** @var MigrationsRepository&MockObject */
    private MigrationsRepository $repository;

    /** @var MetadataStorage&MockObject */
    private MetadataStorage $storage;

    private Rollup $rollup;

    public function setUp(): void
    {
        $this->abstractMigration = $this->createMock(AbstractMigration::class);
        $this->repository        = $this->createMock(FilesystemMigrationsRepository::class);

        $this->storage = $this->createMock(MetadataStorage::class);
        $this->rollup  = new Rollup($this->storage, $this->repository);
    }

    public function testRollup(): void
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);

        $this->repository
           ->expects(self::once())
           ->method('getMigrations')
           ->willReturn(new AvailableMigrationsSet([$m1]));

        $this->storage
           ->expects(self::exactly(1))
           ->method('reset')
           ->with();

        $this->storage
           ->expects(self::exactly(1))
           ->method('complete')
           ->willReturnCallback(static function (ExecutionResult $result): array {
              self::assertEquals(new Version('A'), $result->getVersion());

              return [];
           })->with();

        $this->rollup->rollup();
    }

    public function testRollupTooManyMigrations(): void
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);

        $this->repository
            ->expects(self::once())
            ->method('getMigrations')
            ->willReturn(new AvailableMigrationsSet([$m1, $m2]));

        $this->storage->expects(self::never())->method('reset');
        $this->storage->expects(self::never())->method('complete');
        $this->expectException(RollupFailed::class);
        $this->expectExceptionMessage('Too many migrations.');

        $this->rollup->rollup();
    }

    public function testRollupNoMigrations(): void
    {
        $this->repository
            ->expects(self::any())
            ->method('getMigrations')
            ->willReturn(new AvailableMigrationsSet([]));

        $this->storage->expects(self::never())->method('reset');
        $this->storage->expects(self::never())->method('complete');
        $this->expectException(RollupFailed::class);
        $this->expectExceptionMessage('No migrations found.');

        $this->rollup->rollup();
    }
}
