<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\NoMigrationsToExecute;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsSet;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\MigrationPlanCalculator;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Version\Direction;
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
        $this->migrationPlanCalculator = new MigrationPlanCalculator($this->migrationRepository, $this->metadataStorage);
    }

    public function testPlanForExactVersionWhenNoMigrations() : void
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $migrationList = new AvailableMigrationsList([$m1, $m2, $m3]);

        $this->migrationRepository
            ->expects(self::any())
            ->method('getMigration')
            ->willReturnCallback(static function (Version $version) use ($migrationList) {
                return $migrationList->getMigration($version);
            });

        $plan = $this->migrationPlanCalculator->getPlanForExactVersion(new Version('C'), Direction::UP);

        self::assertCount(1, $plan);
        self::assertSame(Direction::UP, $plan->getDirection());
        self::assertSame(Direction::UP, $plan->getFirst()->getDirection());
        self::assertEquals(new Version('C'), $plan->getFirst()->getVersion());
    }

    /**
     * @param string[] $expectedPlan
     *
     * @dataProvider getPlanUpWhenNoMigrations
     */
    public function testPlanWhenNoMigrations(?string $to, array $expectedPlan, string $direction) : void
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $migrationList = new AvailableMigrationsList([$m1, $m2, $m3]);
        $this->migrationRepository
            ->expects(self::any())
            ->method('getMigrations')
            ->willReturn($migrationList);

        $this->metadataStorage
            ->expects(self::atLeastOnce())
            ->method('getExecutedMigrations')
            ->willReturn(new ExecutedMigrationsSet([]));

        $plan = $this->migrationPlanCalculator->getPlanUntilVersion($to !== null ? new Version($to) : null);

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
            ['C', ['A', 'B', 'C'], Direction::UP],
            [null, ['A', 'B', 'C'], Direction::UP],
        ];
    }

    /**
     * @param string[] $expectedPlan
     *
     * @dataProvider getPlanUpWhenMigrations
     */
    public function testPlanWhenMigrations(?string $to, array $expectedPlan, ?string $direction) : void
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $e1 = new ExecutedMigration(new Version('A'));
        $e2 = new ExecutedMigration(new Version('B'));

        $migrationList = new AvailableMigrationsList([$m1, $m2, $m3]);
        $this->migrationRepository
            ->expects(self::any())
            ->method('getMigrations')
            ->willReturn($migrationList);

        $this->metadataStorage
            ->expects(self::atLeastOnce())
            ->method('getExecutedMigrations')
            ->willReturn(new ExecutedMigrationsSet([$e1, $e2]));

        $plan = $this->migrationPlanCalculator->getPlanUntilVersion($to !== null ? new Version($to) : null);

        self::assertCount(count($expectedPlan), $plan);

        self::assertSame($direction, $plan->getDirection());

        foreach ($expectedPlan as $itemN => $version) {
            self::assertSame($direction, $plan->getItems()[$itemN]->getDirection());
            self::assertEquals(new Version($version), $plan->getItems()[$itemN]->getVersion());
        }
    }

    public function testNoAvailableMigrations() : void
    {
        $this->expectException(NoMigrationsToExecute::class);
        $e1 = new ExecutedMigration(new Version('A'));
        $e2 = new ExecutedMigration(new Version('B'));

        $migrationList = new AvailableMigrationsList([]);
        $this->migrationRepository
            ->expects(self::any())
            ->method('getMigrations')
            ->willReturn($migrationList);

        $this->metadataStorage
            ->expects(self::atLeastOnce())
            ->method('getExecutedMigrations')
            ->willReturn(new ExecutedMigrationsSet([$e1, $e2]));

        $this->migrationPlanCalculator->getPlanUntilVersion();
    }

    public function testGetNewMigrations() : void
    {
        $m1 = new AvailableMigration(new Version('A'), $this->abstractMigration);
        $m2 = new AvailableMigration(new Version('B'), $this->abstractMigration);
        $m3 = new AvailableMigration(new Version('C'), $this->abstractMigration);

        $e1 = new ExecutedMigration(new Version('A'));

        $this->migrationRepository
            ->expects(self::any())
            ->method('getMigrations')
            ->willReturn(new AvailableMigrationsList([$m1, $m2, $m3]));

        $this->metadataStorage
            ->expects(self::atLeastOnce())
            ->method('getExecutedMigrations')
            ->willReturn(new ExecutedMigrationsSet([$e1]));

        $newSet = $this->migrationPlanCalculator->getNewMigrations();

        self::assertSame([$m2, $m3], $newSet->getItems());
    }

    public function testGetExecutedUnavailableMigrations() : void
    {
        $a1 = new AvailableMigration(new Version('A'), $this->abstractMigration);

        $e1 = new ExecutedMigration(new Version('A'));
        $e2 = new ExecutedMigration(new Version('B'));
        $e3 = new ExecutedMigration(new Version('C'));

        $this->migrationRepository
            ->expects(self::any())
            ->method('getMigrations')
            ->willReturn(new AvailableMigrationsList([$a1]));

        $this->metadataStorage
            ->expects(self::atLeastOnce())
            ->method('getExecutedMigrations')
            ->willReturn(new ExecutedMigrationsSet([$e1, $e2, $e3]));

        $newSet = $this->migrationPlanCalculator->getExecutedUnavailableMigrations();

        self::assertSame([$e2, $e3], $newSet->getItems());
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
            [null, ['C'], Direction::UP],
        ];
    }
}
