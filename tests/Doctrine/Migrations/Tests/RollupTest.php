<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\RollupFailed;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Rollup;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RollupTest extends TestCase
{
    /** @var MockObject|AbstractMigration */
    private $abstractMigration;

    /** @var MigrationRepository|MockObject */
    private $repository;

    /** @var MetadataStorage|MockObject */
    private $storage;

    /** @var Rollup */
    private $rollup;

    public function setUp() : void
    {
        $this->abstractMigration = $this->createMock(AbstractMigration::class);
        $this->repository        = $this->createMock(MigrationRepository::class);

        $this->storage = $this->createMock(MetadataStorage::class);
        $this->rollup  = new Rollup($this->storage, $this->repository);
    }

    public function testRollup() : void
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);

        $this->repository
           ->expects(self::once())
           ->method('getMigrations')
           ->willReturn(new AvailableMigrationsList([$m1]));

        $this->storage
           ->expects(self::at(0))->method('reset')->with();
        $this->storage
           ->expects(self::at(1))
           ->method('complete')
           ->willReturnCallback(static function (ExecutionResult $result) : void {
              self::assertEquals(new Version('A'), $result->getVersion());
           })->with();

        $this->rollup->rollup();
    }

    public function testRollupTooManyMigrations() : void
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);

        $this->repository
            ->expects(self::once())
            ->method('getMigrations')
            ->willReturn(new AvailableMigrationsList([$m1, $m2]));

        $this->storage->expects(self::never())->method('reset');
        $this->storage->expects(self::never())->method('complete');
        $this->expectException(RollupFailed::class);
        $this->expectExceptionMessage('Too many migrations.');

        $this->rollup->rollup();
    }

    public function testRollupNoMigrations() : void
    {
        $this->repository
            ->expects(self::any())
            ->method('getMigrations')
            ->willReturn(new AvailableMigrationsList([]));

        $this->storage->expects(self::never())->method('reset');
        $this->storage->expects(self::never())->method('complete');
        $this->expectException(RollupFailed::class);
        $this->expectExceptionMessage('No migrations found.');

        $this->rollup->rollup();
    }
}
