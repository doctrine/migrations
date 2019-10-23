<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Metadata;

use DateTimeImmutable;
use Doctrine\Migrations\Exception\MigrationNotExecuted;
use Doctrine\Migrations\Exception\NoMigrationsFoundWithCriteria;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsSet;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;

class ExecutedMigrationSetTest extends TestCase
{
    public function testFirstWhenEmpty() : void
    {
        $this->expectException(NoMigrationsFoundWithCriteria::class);
        $this->expectExceptionMessage('Could not find any migrations matching your criteria (first).');
        $set = new ExecutedMigrationsSet([]);
        $set->getFirst();
    }

    public function testLastWhenEmpty() : void
    {
        $this->expectException(NoMigrationsFoundWithCriteria::class);
        $this->expectExceptionMessage('Could not find any migrations matching your criteria (last).');
        $set = new ExecutedMigrationsSet([]);
        $set->getLast();
    }

    public function testFirst() : void
    {
        $m1 = new ExecutedMigration(new Version('A'));
        $m2 = new ExecutedMigration(new Version('B'));
        $m3 = new ExecutedMigration(new Version('C'));

        $set = new ExecutedMigrationsSet([$m1, $m2, $m3]);
        self::assertSame($m1, $set->getFirst());
        self::assertSame($m2, $set->getFirst(1));
    }

    public function testLast() : void
    {
        $m1 = new ExecutedMigration(new Version('A'));
        $m2 = new ExecutedMigration(new Version('B'));
        $m3 = new ExecutedMigration(new Version('C'));

        $set = new ExecutedMigrationsSet([$m1, $m2, $m3]);
        self::assertSame($m3, $set->getLast());
        self::assertSame($m2, $set->getLast(-1));
    }

    public function testItems() : void
    {
        $m1 = new ExecutedMigration(new Version('A'));
        $m2 = new ExecutedMigration(new Version('B'));
        $m3 = new ExecutedMigration(new Version('C'));

        $set = new ExecutedMigrationsSet([$m1, $m2, $m3]);
        self::assertSame([$m1, $m2, $m3], $set->getItems());
    }

    public function testCount() : void
    {
        $m1 = new ExecutedMigration(new Version('A'));
        $m2 = new ExecutedMigration(new Version('B'));
        $m3 = new ExecutedMigration(new Version('C'));

        $set = new ExecutedMigrationsSet([$m1, $m2, $m3]);
        self::assertCount(3, $set);
    }

    public function testGetMigration() : void
    {
        $m1 = new ExecutedMigration(new Version('A'));
        $m2 = new ExecutedMigration(new Version('B'));
        $m3 = new ExecutedMigration(new Version('C'));

        $set = new ExecutedMigrationsSet([$m1, $m2, $m3]);
        self::assertSame($m2, $set->getMigration(new Version('B')));
    }

    public function testGetMigrationThrowsExceptionIfNotExisting() : void
    {
        $this->expectException(MigrationNotExecuted::class);
        $m1 = new ExecutedMigration(new Version('A'));
        $m2 = new ExecutedMigration(new Version('B'));
        $m3 = new ExecutedMigration(new Version('C'));

        $set = new ExecutedMigrationsSet([$m1, $m2, $m3]);
        $set->getMigration(new Version('D'));
    }

    public function testHasMigration() : void
    {
        $m1 = new ExecutedMigration(new Version('A'));
        $m2 = new ExecutedMigration(new Version('B'));
        $m3 = new ExecutedMigration(new Version('C'));

        $set = new ExecutedMigrationsSet([$m1, $m2, $m3]);
        self::assertTrue($set->hasMigration(new Version('B')));
        self::assertFalse($set->hasMigration(new Version('D')));
    }

    public function testExecutedMigration() : void
    {
        $m1 = new ExecutedMigration(new Version('A'));

        self::assertEquals(new Version('A'), $m1->getVersion());
        self::assertNull($m1->getExecutedAt());
        self::assertNull($m1->getExecutionTime());
    }

    public function testExecutedMigrationWithTiming() : void
    {
        $date = new DateTimeImmutable();
        $m1   = new ExecutedMigration(new Version('A'), $date, 123);

        self::assertSame($date, $m1->getExecutedAt());
        self::assertSame(123, $m1->getExecutionTime());
    }
}
