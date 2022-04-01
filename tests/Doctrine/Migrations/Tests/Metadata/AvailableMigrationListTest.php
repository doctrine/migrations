<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Metadata;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\MigrationNotAvailable;
use Doctrine\Migrations\Exception\NoMigrationsFoundWithCriteria;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;

class AvailableMigrationListTest extends TestCase
{
    private AbstractMigration $abstractMigration;

    public function setUp(): void
    {
        $this->abstractMigration = $this->createMock(AbstractMigration::class);
    }

    public function testFirstWhenEmpty(): void
    {
        $this->expectException(NoMigrationsFoundWithCriteria::class);
        $this->expectExceptionMessage('Could not find any migrations matching your criteria (first).');
        $set = new AvailableMigrationsList([]);
        $set->getFirst();
    }

    public function testLastWhenEmpty(): void
    {
        $this->expectException(NoMigrationsFoundWithCriteria::class);
        $this->expectExceptionMessage('Could not find any migrations matching your criteria (last).');
        $set = new AvailableMigrationsList([]);
        $set->getLast();
    }

    public function testFirst(): void
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $set = new AvailableMigrationsList([$m1, $m2, $m3]);
        self::assertSame($m1, $set->getFirst());
        self::assertSame($m2, $set->getFirst(1));
    }

    public function testLast(): void
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $set = new AvailableMigrationsList([$m1, $m2, $m3]);
        self::assertSame($m3, $set->getLast());
        self::assertSame($m2, $set->getLast(-1));
    }

    public function testItems(): void
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $set = new AvailableMigrationsList([$m1, $m2, $m3]);
        self::assertSame([$m1, $m2, $m3], $set->getItems());
    }

    public function testCount(): void
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $set = new AvailableMigrationsList([$m1, $m2, $m3]);
        self::assertCount(3, $set);
    }

    public function testGetMigration(): void
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $set = new AvailableMigrationsList([$m1, $m2, $m3]);
        self::assertSame($m2, $set->getMigration(new Version('B')));
    }

    public function testGetMigrationThrowsExceptionIfNotExisting(): void
    {
        $this->expectException(MigrationNotAvailable::class);
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $set = new AvailableMigrationsList([$m1, $m2, $m3]);
        $set->getMigration(new Version('D'));
    }

    public function testHasMigration(): void
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $set = new AvailableMigrationsList([$m1, $m2, $m3]);
        self::assertTrue($set->hasMigration(new Version('B')));
        self::assertFalse($set->hasMigration(new Version('D')));
    }

    public function testAvailableMigration(): void
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);

        self::assertEquals(new Version('A'), $m1->getVersion());
        self::assertSame($this->abstractMigration, $m1->getMigration());
    }

    public function testNewSubset(): void
    {
        $m1           = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2           = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3           = new AvailableMigration(new Version('C'), $this->abstractMigration);
        $availableSet = new AvailableMigrationsList([$m1, $m2, $m3]);

        $executedSet = new ExecutedMigrationsList([
            new ExecutedMigration(new Version('A')),
            new ExecutedMigration(new Version('B')),
        ]);

        $newSubset = $availableSet->newSubset($executedSet);
        self::assertCount(1, $newSubset);
        self::assertTrue($newSubset->hasMigration(new Version('C')));
    }
}
