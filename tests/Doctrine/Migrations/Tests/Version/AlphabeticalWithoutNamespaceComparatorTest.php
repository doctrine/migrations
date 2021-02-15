<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Version;

use Doctrine\Migrations\Version\AlphabeticalWithoutNamespaceComparator;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;

final class AlphabeticalWithoutNamespaceComparatorTest extends TestCase
{
    /**
     * @return iterable[]
     */
    public function provideVersion(): iterable
    {
        yield [new Version('a'), new Version('b'), -1];
        yield [new Version('b'), new Version('a'), 1];
        yield [new Version('a\b'), new Version('b\a'), 1];
        yield [new Version('b\a'), new Version('a\b'), -1];
    }

    /** @dataProvider provideVersion */
    public function testOrder(Version $a, Version $b, int $expected): void
    {
        $sUT = new AlphabeticalWithoutNamespaceComparator();

        self::assertSame($expected, $sUT->compare($a, $b));
    }
}
