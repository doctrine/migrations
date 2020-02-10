<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Version;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsSet;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\MigrationPlanCalculator;
use Doctrine\Migrations\Version\SortedMigrationPlanCalculator;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use function count;

final class MigrationPlanCalculatorTest extends TestCase
{
    /** @var MigrationPlanCalculator */
    private $migrationPlanCalculator;

    /** @var MockObject|MigrationRepository */
    private $migrationRepository;

    /** @var MockObject|MetadataStorage */
    private $metadataStorage;

    /** @var MockObject|AbstractMigration */
    private $abstractMigration;

    protected function setUp() : void
    {
        $this->abstractMigration = $this->createMock(AbstractMigration::class);

        $this->migrationRepository     = $this->createMock(MigrationRepository::class);
        $this->metadataStorage         = $this->createMock(MetadataStorage::class);
        $this->migrationPlanCalculator = new SortedMigrationPlanCalculator($this->migrationRepository, $this->metadataStorage);

        $m = [
            'A' => new AvailableMigration(new Version('A'), $this->abstractMigration),
            'B' => new AvailableMigration(new Version('B'), $this->abstractMigration),
            'C' => new AvailableMigration(new Version('C'), $this->abstractMigration),
        ];

        $migrationList = new AvailableMigrationsList($m);
        $this->migrationRepository
            ->expects(self::any())
            ->method('hasMigration')
            ->willReturnCallback(static function ($version) use ($m) {
                return isset($m[$version]);
            });

        $this->migrationRepository
            ->expects(self::any())
            ->method('getMigrations')
            ->willReturn($migrationList);
    }

    public function testPlanForVersionsWhenNoMigrations() : void
    {
        $plan = $this->migrationPlanCalculator->getPlanForVersions([new Version('C')], Direction::UP);

        self::assertCount(1, $plan);
        self::assertSame(Direction::UP, $plan->getDirection());
        self::assertSame(Direction::UP, $plan->getFirst()->getDirection());
        self::assertEquals(new Version('C'), $plan->getFirst()->getVersion());
    }

    public function testPlanForMultipleVersionsAreSortedUp() : void
    {
        $plan = $this->migrationPlanCalculator->getPlanForVersions([new Version('C'), new Version('A')], Direction::UP);

        self::assertCount(2, $plan);
        self::assertSame(Direction::UP, $plan->getDirection());
        self::assertSame(Direction::UP, $plan->getFirst()->getDirection());

        self::assertEquals(new Version('A'), $plan->getFirst()->getVersion());
        self::assertEquals(new Version('C'), $plan->getLast()->getVersion());
    }

    public function testPlanForMultipleVersionsAreSortedDown() : void
    {
        $plan = $this->migrationPlanCalculator->getPlanForVersions([new Version('C'), new Version('A')], Direction::UP);

        self::assertCount(2, $plan);
        self::assertSame(Direction::UP, $plan->getDirection());
        self::assertSame(Direction::UP, $plan->getFirst()->getDirection());

        self::assertEquals(new Version('A'), $plan->getFirst()->getVersion());
        self::assertEquals(new Version('C'), $plan->getLast()->getVersion());
    }

    public function testPlanForNoMigration() : void
    {
        $this->expectException(MigrationClassNotFound::class);
        $this->expectExceptionMessage('Migration class "D" was not found?');

        $this->migrationPlanCalculator->getPlanForVersions([new Version('D')], Direction::UP);
    }

    public function testPlanForNoVersions() : void
    {
        $plan = $this->migrationPlanCalculator->getPlanForVersions([], Direction::UP);

        self::assertCount(0, $plan);
        self::assertSame(Direction::UP, $plan->getDirection());
    }

    /**
     * @param string[] $expectedPlan
     *
     * @dataProvider getPlanUpWhenNoMigrations
     */
    public function testPlanWhenNoMigrations(string $to, array $expectedPlan, string $direction) : void
    {
        $this->metadataStorage
            ->expects(self::atLeastOnce())
            ->method('getExecutedMigrations')
            ->willReturn(new ExecutedMigrationsSet([]));

        $plan = $this->migrationPlanCalculator->getPlanUntilVersion(new Version($to));

        self::assertSame($direction, $plan->getDirection());
        self::assertCount(count($expectedPlan), $plan);

        foreach ($expectedPlan as $itemN => $version) {
            self::assertSame($direction, $plan->getItems()[$itemN]->getDirection());
            self::assertEquals(new Version($version), $plan->getItems()[$itemN]->getVersion());
        }
    }

    /**
     * @return mixed[]
     */
    public function getPlanUpWhenNoMigrations() : array
    {
        return [
            ['A', ['A'], Direction::UP],
            ['B', ['A', 'B'], Direction::UP],
            ['C', ['A', 'B', 'C'], Direction::UP],
        ];
    }

    /**
     * @param string[] $expectedPlan
     *
     * @dataProvider getPlanUpWhenMigrations
     */
    public function testPlanWhenMigrations(string $to, array $expectedPlan, string $direction) : void
    {
        $e1 = new ExecutedMigration(new Version('A'));
        $e2 = new ExecutedMigration(new Version('B'));

        $this->metadataStorage
            ->expects(self::atLeastOnce())
            ->method('getExecutedMigrations')
            ->willReturn(new ExecutedMigrationsSet([$e1, $e2]));

        $plan = $this->migrationPlanCalculator->getPlanUntilVersion(new Version($to));

        self::assertCount(count($expectedPlan), $plan);

        self::assertSame($direction, $plan->getDirection());

        foreach ($expectedPlan as $itemN => $version) {
            self::assertSame($direction, $plan->getItems()[$itemN]->getDirection());
            self::assertEquals(new Version($version), $plan->getItems()[$itemN]->getVersion());
        }
    }

    public function testTargetMigrationNotFound() : void
    {
        $this->expectException(MigrationClassNotFound::class);
        $this->expectExceptionMessage('ss');
        $this->migrationPlanCalculator->getPlanUntilVersion(new Version('D'));
    }

    /**
     * @return mixed[]
     */
    public function getPlanUpWhenMigrations() : array
    {
        return [
            ['0', ['B', 'A'], Direction::DOWN],
            ['A', ['B'], Direction::DOWN],
            ['B', [], Direction::UP],
            ['C', ['C'], Direction::UP],
        ];
    }
}
