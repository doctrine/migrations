<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools;

use Doctrine\Migrations\MigrationsVersion;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class MigrationsVersionTest extends TestCase
{
    public function testVersionNumber() : void
    {
        $class = new ReflectionClass(MigrationsVersion::class);

        $property = $class->getProperty('version');
        $property->setAccessible(true);

        $versionNumber = $property->getValue(new MigrationsVersion());
        self::assertRegExp('/^v[0-9]{1,}\.[0-9]{1,}\.[0-9]{1,}(-([a-z]){1,}[0-9]{1,}){0,1}$/', $versionNumber);
        self::assertEquals($versionNumber, MigrationsVersion::VERSION());
    }

    public function testIsACustomPharBuild() : void
    {
        $class = new ReflectionClass(MigrationsVersion::class);

        $method = $class->getMethod('isACustomPharBuild');
        $method->setAccessible(true);

        self::assertFalse($method->invokeArgs(new MigrationsVersion(), ['@git-version@']), 'This is not a custom phar build.');
        self::assertTrue($method->invokeArgs(new MigrationsVersion(), ['v1.0.0-alpha3-125435']), 'This has been replaced by box and is thus a phar build.');
    }
}
