<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Metadata;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\MigrationNotAvailable;
use Doctrine\Migrations\Exception\MigrationNotExecuted;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsSet;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;

class AvailableMigrationListTest extends TestCase
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
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $set = new AvailableMigrationsList([$m1, $m2, $m3]);
        self::assertSame($m1, $set->getFirst());
        self::assertSame($m2, $set->getFirst(1));
    }

    public function testLast()
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $set = new AvailableMigrationsList([$m1, $m2, $m3]);
        self::assertSame($m3, $set->getLast());
        self::assertSame($m2, $set->getLast(-1));
    }

    public function testItems()
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $set = new AvailableMigrationsList([$m1, $m2, $m3]);
        self::assertSame([$m1, $m2, $m3], $set->getItems());
    }

    public function testCount()
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $set = new AvailableMigrationsList([$m1, $m2, $m3]);
        self::assertCount(3, $set);
    }

    public function testGetMigration()
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $set = new AvailableMigrationsList([$m1, $m2, $m3]);
        self::assertSame($m2, $set->getMigration(new Version('B')));
    }

    public function testGetMigrationThrowsExceptionIfNotExisting()
    {
        $this->expectException(MigrationNotAvailable::class);
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $set = new AvailableMigrationsList([$m1, $m2, $m3]);
        $set->getMigration(new Version('D'));
    }

    public function testHasMigration()
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $set = new AvailableMigrationsList([$m1, $m2, $m3]);
        self::assertTrue($set->hasMigration(new Version('B')));
        self::assertFalse($set->hasMigration(new Version('D')));
    }

    public function testGetNewMigrations()
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $e1 = new ExecutedMigration(new Version('A'));

        $executedSet = new ExecutedMigrationsSet([$e1]);

        $set = new AvailableMigrationsList([$m1, $m2, $m3]);

        $newSet = $set->getNewMigrations($executedSet);

        self::assertInstanceOf(AvailableMigrationsList::class, $newSet);
        self::assertSame([$m2, $m3], $newSet->getItems());
    }


    public function testAvailableMigration()
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);

        self::assertEquals(new Version('A'), $m1->getVersion());
        self::assertSame($this->abstractMigration, $m1->getMigration());
    }

}
