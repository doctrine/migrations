<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Generator;

use Doctrine\Migrations\Generator\ClassNameGenerator;
use PHPUnit\Framework\TestCase;

class ClassNameGeneratorTest extends TestCase
{
    public function testName(): void
    {
        $generator = new ClassNameGenerator();
        $fqcn      = $generator->generateClassName('Foo');

        self::assertMatchesRegularExpression('/^Foo\\\\Version[0-9]{14}$/', $fqcn);
    }
}
