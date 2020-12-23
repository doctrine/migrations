<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools;

use Doctrine\Migrations\Tools\BooleanStringFormatter;
use PHPUnit\Framework\TestCase;

class BooleanStringFormatterTest extends TestCase
{
    public function testToBoolean(): void
    {
        self::assertTrue(BooleanStringFormatter::toBoolean('true', false));
        self::assertTrue(BooleanStringFormatter::toBoolean('1', false));
        self::assertFalse(BooleanStringFormatter::toBoolean('false', false));
        self::assertFalse(BooleanStringFormatter::toBoolean('0', false));
        self::assertFalse(BooleanStringFormatter::toBoolean('werwerwer', false));
        self::assertTrue(BooleanStringFormatter::toBoolean('werwerwer', true));
    }
}
