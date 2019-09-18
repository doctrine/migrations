<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Metadata;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\MigrationNotExecuted;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsSet;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;

class MigrationPlanListTest extends TestCase
{
    /**
     * @var AbstractMigration
     */
    private $abstractMigration;

    /**
     * @var MigrationPlan
     */
    private $set;
    /**
     * @var MigrationPlan
     */
    private $m1;
    /**
     * @var MigrationPlan
     */
    private $m2;
    /**
     * @var MigrationPlan
     */
    private $m3;

    public function setUp()
    {
        $this->abstractMigration = $this->createMock(AbstractMigration::class);
        $this->m1 = new MigrationPlan(new Version('A'), $this->abstractMigration, Direction::UP);
        $this->m2 = new MigrationPlan(new Version('B'), $this->abstractMigration, Direction::UP);
        $this->m3 = new MigrationPlan(new Version('C'), $this->abstractMigration, Direction::UP);

        $this->set = new MigrationPlanList([$this->m1, $this->m2, $this->m3], Direction::UP);
    }

    public function testFirst()
    {
        self::assertSame($this->m1, $this->set->getFirst());
    }

    public function testLast()
    {
        self::assertSame($this->m3, $this->set->getLast());
    }

    public function testItems()
    {
        self::assertSame([$this->m1, $this->m2, $this->m3], $this->set->getItems());
    }

    public function testCount()
    {
        self::assertCount(3, $this->set);
    }

    public function testDirection()
    {
        self::assertSame(Direction::UP, $this->set->getDirection());
        self::assertSame(Direction::UP, $this->set->getFirst()->getDirection());
    }

    public function testPlan()
    {
        self::assertSame(Direction::UP, $this->m1->getDirection());
        self::assertSame($this->abstractMigration, $this->m1->getMigration());
        self::assertEquals(new Version('A'), $this->m1->getVersion());
        self::assertNull($this->m1->getResult());
    }

    public function testPlanResult()
    {
        $result = new ExecutionResult(new Version('A'),  Direction::UP);
        $this->m1->setResult($result);

        self::assertSame($result, $this->m1->getResult());
    }
}
