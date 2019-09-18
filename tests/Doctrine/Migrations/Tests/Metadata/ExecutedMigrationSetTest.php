<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Metadata;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\MigrationNotExecuted;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsSet;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;

class ExecutedMigrationSetTest extends TestCase
{
    /**
     * @var AbstractMigration
     */
    private $abstractMigration;

    public function setUp()
    {
        $this->abstractMigration = $this->createMock(AbstractMigration::class);
    }

    public function testFirst()
    {
        $m1 = new ExecutedMigration(new Version('A'));
        $m2 = new ExecutedMigration(new Version('B'));
        $m3 = new ExecutedMigration(new Version('C'));

        $set = new ExecutedMigrationsSet([$m1, $m2, $m3]);
        self::assertSame($m1, $set->getFirst());
        self::assertSame($m2, $set->getFirst(1));
    }

    public function testLast()
    {
        $m1 = new ExecutedMigration(new Version('A'));
        $m2 = new ExecutedMigration(new Version('B'));
        $m3 = new ExecutedMigration(new Version('C'));

        $set = new ExecutedMigrationsSet([$m1, $m2, $m3]);
        self::assertSame($m3, $set->getLast());
        self::assertSame($m2, $set->getLast(-1));
    }

    public function testItems()
    {
        $m1 = new ExecutedMigration(new Version('A'));
        $m2 = new ExecutedMigration(new Version('B'));
        $m3 = new ExecutedMigration(new Version('C'));

        $set = new ExecutedMigrationsSet([$m1, $m2, $m3]);
        self::assertSame([$m1, $m2, $m3], $set->getItems());
    }

    public function testCount()
    {
        $m1 = new ExecutedMigration(new Version('A'));
        $m2 = new ExecutedMigration(new Version('B'));
        $m3 = new ExecutedMigration(new Version('C'));

        $set = new ExecutedMigrationsSet([$m1, $m2, $m3]);
        self::assertCount(3, $set);
    }

    public function testGetMigration()
    {
        $m1 = new ExecutedMigration(new Version('A'));
        $m2 = new ExecutedMigration(new Version('B'));
        $m3 = new ExecutedMigration(new Version('C'));

        $set = new ExecutedMigrationsSet([$m1, $m2, $m3]);
        self::assertSame($m2, $set->getMigration(new Version('B')));
    }

    public function testGetMigrationThrowsExceptionIfNotExisting()
    {
        $this->expectException(MigrationNotExecuted::class);
        $m1 = new ExecutedMigration(new Version('A'));
        $m2 = new ExecutedMigration(new Version('B'));
        $m3 = new ExecutedMigration(new Version('C'));

        $set = new ExecutedMigrationsSet([$m1, $m2, $m3]);
        $set->getMigration(new Version('D'));
    }

    public function testHasMigration()
    {
        $m1 = new ExecutedMigration(new Version('A'));
        $m2 = new ExecutedMigration(new Version('B'));
        $m3 = new ExecutedMigration(new Version('C'));

        $set = new ExecutedMigrationsSet([$m1, $m2, $m3]);
        self::assertTrue($set->hasMigration(new Version('B')));
        self::assertFalse($set->hasMigration(new Version('D')));
    }

    public function testGetNewMigrations()
    {
        $a1 = new AvailableMigration(new Version('A'), $this->abstractMigration);

        $m1 = new ExecutedMigration(new Version('A'));
        $m2 = new ExecutedMigration(new Version('B'));
        $m3 = new ExecutedMigration(new Version('C'));

        $executedSet = new ExecutedMigrationsSet([$m1, $m2, $m3]);

        $availableSet = new AvailableMigrationsList([$a1]);

        $newSet = $executedSet->getExecutedUnavailableMigrations($availableSet);
        self::assertInstanceOf(ExecutedMigrationsSet::class, $newSet);

        self::assertSame([$m2, $m3], $newSet->getItems());
    }

    public function testExecutedMigration()
    {

        $m1 = new ExecutedMigration(new Version('A'));

        self::assertEquals(new Version('A'), $m1->getVersion());
        self::assertNull($m1->getExecutedAt());
        self::assertNull($m1->getExecutionTime());
    }

    public function testExecutedMigrationWithTiming()
    {
        $date = new \DateTime();
        $m1 = new ExecutedMigration(new Version('A'), $date, 123);

        self::assertSame($date, $m1->getExecutedAt());
        self::assertSame(123, $m1->getExecutionTime());
    }
}

