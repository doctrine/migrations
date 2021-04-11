<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Generator;

use Doctrine\Migrations\Generator\ClassNameGenerator;
use PHPUnit\Framework\TestCase;

use function method_exists;

class ClassNameGeneratorTest extends TestCase
{
    public function testName(): void
    {
        $generator = new ClassNameGenerator();
        $fqcn      = $generator->generateClassName('Foo');

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            self::assertMatchesRegularExpression('/^Foo\\\\Version[0-9]{14}$/', $fqcn);
        } else {
            self::assertRegExp('/^Foo\\\\Version[0-9]{14}$/', $fqcn);
        }
    }
}
