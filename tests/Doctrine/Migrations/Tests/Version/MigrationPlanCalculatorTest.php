<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Version;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsSet;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Version\AlphabeticalComparator;
use Doctrine\Migrations\Version\Comparator;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\MigrationPlanCalculator;
use Doctrine\Migrations\Version\SortedMigrationPlanCalculator;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function count;
use function strcmp;

final class MigrationPlanCalculatorTest extends TestCase
{
    private MigrationPlanCalculator $migrationPlanCalculator;

    /** @var MigrationsRepository&MockObject */
    private MigrationsRepository $migrationRepository;

    /** @var MetadataStorage&MockObject */
    private MetadataStorage $metadataStorage;

    /** @var AbstractMigration&MockObject */
    private AbstractMigration $abstractMigration;

    protected function setUp(): void
    {
        $this->abstractMigration       = $this->createMock(AbstractMigration::class);
        $this->migrationRepository     = $this->createMock(MigrationsRepository::class);
        $this->metadataStorage         = $this->createMock(MetadataStorage::class);
        $this->migrationPlanCalculator = new SortedMigrationPlanCalculator($this->migrationRepository, $this->metadataStorage, new AlphabeticalComparator());

        $m = [
            'B' => new AvailableMigration(new Version('B'), $this->abstractMigration),
            'A' => new AvailableMigration(new Version('A'), $this->abstractMigration),
            'C' => new AvailableMigration(new Version('C'), $this->abstractMigration),
        ];

        $migrationList = new AvailableMigrationsSet($m);
        $this->migrationRepository
            ->expects(self::any())
            ->method('hasMigration')
            ->willReturnCallback(static function ($version) use ($m): bool {
                return isset($m[$version]);
            });

        $this->migrationRepository
            ->expects(self::any())
            ->method('getMigrations')
            ->willReturn($migrationList);
    }

    public function testPlanForVersionsWhenNoMigrations(): void
    {
        $plan = $this->migrationPlanCalculator->getPlanForVersions([new Version('C')], Direction::UP);

        self::assertCount(1, $plan);
        self::assertSame(Direction::UP, $plan->getDirection());
        self::assertSame(Direction::UP, $plan->getFirst()->getDirection());
        self::assertEquals(new Version('C'), $plan->getFirst()->getVersion());
    }

    public function testPlanForMultipleVersionsAreSortedUp(): void
    {
        $plan = $this->migrationPlanCalculator->getPlanForVersions([new Version('C'), new Version('A')], Direction::UP);

        self::assertCount(2, $plan);
        self::assertSame(Direction::UP, $plan->getDirection());
        self::assertSame(Direction::UP, $plan->getFirst()->getDirection());

        self::assertEquals(new Version('A'), $plan->getFirst()->getVersion());
        self::assertEquals(new Version('C'), $plan->getLast()->getVersion());
    }

    public function testPlanForMultipleVersionsAreSortedDown(): void
    {
        $plan = $this->migrationPlanCalculator->getPlanForVersions([new Version('C'), new Version('A')], Direction::UP);

        self::assertCount(2, $plan);
        self::assertSame(Direction::UP, $plan->getDirection());
        self::assertSame(Direction::UP, $plan->getFirst()->getDirection());

        self::assertEquals(new Version('A'), $plan->getFirst()->getVersion());
        self::assertEquals(new Version('C'), $plan->getLast()->getVersion());
    }

    public function testPlanForNoMigration(): void
    {
        $this->expectException(MigrationClassNotFound::class);
        $this->expectExceptionMessage('Migration class "D" was not found?');

        $this->migrationPlanCalculator->getPlanForVersions([new Version('D')], Direction::UP);
    }

    public function testPlanForNoVersions(): void
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
    public function testPlanWhenNoMigrations(string $to, array $expectedPlan, string $direction): void
    {
        $this->metadataStorage
            ->expects(self::atLeastOnce())
            ->method('getExecutedMigrations')
            ->willReturn(new ExecutedMigrationsList([]));

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
    public function getPlanUpWhenNoMigrations(): array
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
    public function testPlanWhenMigrations(string $to, array $expectedPlan, string $direction): void
    {
        $e1 = new ExecutedMigration(new Version('A'));
        $e2 = new ExecutedMigration(new Version('B'));

        $this->metadataStorage
            ->expects(self::atLeastOnce())
            ->method('getExecutedMigrations')
            ->willReturn(new ExecutedMigrationsList([$e1, $e2]));

        $plan = $this->migrationPlanCalculator->getPlanUntilVersion(new Version($to));

        self::assertCount(count($expectedPlan), $plan);

        self::assertSame($direction, $plan->getDirection());

        foreach ($expectedPlan as $itemN => $version) {
            self::assertSame($direction, $plan->getItems()[$itemN]->getDirection());
            self::assertEquals(new Version($version), $plan->getItems()[$itemN]->getVersion());
        }
    }

    public function testTargetMigrationNotFound(): void
    {
        $this->expectException(MigrationClassNotFound::class);
        $this->expectExceptionMessage('ss');
        $this->migrationPlanCalculator->getPlanUntilVersion(new Version('D'));
    }

    /**
     * @return mixed[]
     */
    public function getPlanUpWhenMigrations(): array
    {
        return [
            ['0', ['B', 'A'], Direction::DOWN],
            ['A', ['B'], Direction::DOWN],
            ['B', [], Direction::UP],
            ['C', ['C'], Direction::UP],
        ];
    }

    /**
     * @param string[] $expectedPlan
     *
     * @dataProvider getPlanUpWhenMigrationsOutOfOrder
     */
    public function testPlanWhenMigrationsOutOfOrder(string $to, array $expectedPlan, string $direction): void
    {
        $e1 = new ExecutedMigration(new Version('B'));
        $e2 = new ExecutedMigration(new Version('C'));

        $this->metadataStorage
            ->expects(self::atLeastOnce())
            ->method('getExecutedMigrations')
            ->willReturn(new ExecutedMigrationsList([$e1, $e2]));

        $plan = $this->migrationPlanCalculator->getPlanUntilVersion(new Version($to));

        self::assertCount(count($expectedPlan), $plan);

        self::assertSame($direction, $plan->getDirection());

        foreach ($expectedPlan as $itemN => $version) {
            self::assertSame($direction, $plan->getItems()[$itemN]->getDirection());
            self::assertEquals(new Version($version), $plan->getItems()[$itemN]->getVersion());
        }
    }

    /**
     * @return mixed[]
     */
    public function getPlanUpWhenMigrationsOutOfOrder(): array
    {
        return [
            ['C',['A'],Direction::UP],
        ];
    }

    public function testCustomMigrationSorting(): void
    {
        $reverseSorter           = new class implements Comparator {
            public function compare(Version $a, Version $b): int
            {
                return strcmp((string) $b, (string) $a);
            }
        };
        $migrationPlanCalculator = new SortedMigrationPlanCalculator(
            $this->migrationRepository,
            $this->metadataStorage,
            $reverseSorter
        );

        $migrations = $migrationPlanCalculator->getMigrations();

        self::assertCount(3, $migrations);

        // reverse order
        self::assertSame('A', (string) $migrations->getItems()[2]->getVersion());
        self::assertSame('B', (string) $migrations->getItems()[1]->getVersion());
        self::assertSame('C', (string) $migrations->getItems()[0]->getVersion());
    }
}
