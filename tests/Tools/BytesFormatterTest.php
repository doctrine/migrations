<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools;

use Doctrine\Migrations\Tools\BytesFormatter;
use PHPUnit\Framework\TestCase;

class BytesFormatterTest extends TestCase
{
    public function testFormatBytes(): void
    {
        self::assertSame('1000', BytesFormatter::formatBytes(1000));
        self::assertSame('97.66K', BytesFormatter::formatBytes(100000));
        self::assertSame('9.54M', BytesFormatter::formatBytes(10000000));
        self::assertSame('93.13G', BytesFormatter::formatBytes(100000000000));
        self::assertSame('90.95T', BytesFormatter::formatBytes(100000000000000));
    }
}
