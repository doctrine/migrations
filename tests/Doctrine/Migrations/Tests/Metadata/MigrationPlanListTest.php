<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Metadata;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\NoMigrationsFoundWithCriteria;
use Doctrine\Migrations\Exception\PlanAlreadyExecuted;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;

class MigrationPlanListTest extends TestCase
{
    private AbstractMigration $abstractMigration;

    private MigrationPlanList $set;
    private MigrationPlan $m1;
    private MigrationPlan $m2;
    private MigrationPlan $m3;

    public function setUp(): void
    {
        $this->abstractMigration = $this->createMock(AbstractMigration::class);
        $this->m1                = new MigrationPlan(new Version('A'), $this->abstractMigration, Direction::UP);
        $this->m2                = new MigrationPlan(new Version('B'), $this->abstractMigration, Direction::UP);
        $this->m3                = new MigrationPlan(new Version('C'), $this->abstractMigration, Direction::UP);

        $this->set = new MigrationPlanList([$this->m1, $this->m2, $this->m3], Direction::UP);
    }

    public function testFirstWhenEmpty(): void
    {
        $this->expectException(NoMigrationsFoundWithCriteria::class);
        $this->expectExceptionMessage('Could not find any migrations matching your criteria (first).');
        $set = new MigrationPlanList([], Direction::UP);
        $set->getFirst();
    }

    public function testLastWhenEmpty(): void
    {
        $this->expectException(NoMigrationsFoundWithCriteria::class);
        $this->expectExceptionMessage('Could not find any migrations matching your criteria (last).');
        $set = new MigrationPlanList([], Direction::UP);
        $set->getLast();
    }

    public function testFirst(): void
    {
        self::assertSame($this->m1, $this->set->getFirst());
    }

    public function testLast(): void
    {
        self::assertSame($this->m3, $this->set->getLast());
    }

    public function testItems(): void
    {
        self::assertSame([$this->m1, $this->m2, $this->m3], $this->set->getItems());
    }

    public function testCount(): void
    {
        self::assertCount(3, $this->set);
    }

    public function testDirection(): void
    {
        self::assertSame(Direction::UP, $this->set->getDirection());
        self::assertSame(Direction::UP, $this->set->getFirst()->getDirection());
    }

    public function testPlan(): void
    {
        self::assertSame(Direction::UP, $this->m1->getDirection());
        self::assertSame($this->abstractMigration, $this->m1->getMigration());
        self::assertEquals(new Version('A'), $this->m1->getVersion());
        self::assertNull($this->m1->getResult());
    }

    public function testPlanResultCanBeSetOnlyOnce(): void
    {
        $this->expectException(PlanAlreadyExecuted::class);
        $this->expectExceptionMessage('This plan was already marked as executed.');

        $result = new ExecutionResult(new Version('A'), Direction::UP);
        $this->m1->markAsExecuted($result);
        $this->m1->markAsExecuted($result);
    }

    public function testPlanResult(): void
    {
        $result = new ExecutionResult(new Version('A'), Direction::UP);
        $this->m1->markAsExecuted($result);

        self::assertSame($result, $this->m1->getResult());
    }
}
