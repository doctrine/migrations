<?php
namespace Doctrine\DBAL\Migrations\Tests\Tools;

use Doctrine\DBAL\Migrations\MigrationsVersion;
use PHPUnit\Framework\TestCase;

class MigrationsVersionTest extends TestCase
{
    /** @var string */
    private $MigrationVersionClass = MigrationsVersion::class;

    public function testVersionNumber()
    {
        $class    = new \ReflectionClass($this->MigrationVersionClass);
        $property = $class->getProperty('version');
        $property->setAccessible(true);

        $versionNumber = $property->getValue(new MigrationsVersion());
        self::assertRegExp('/^v[0-9]{1,}\.[0-9]{1,}\.[0-9]{1,}(-([a-z]){1,}[0-9]{1,}){0,1}$/', $versionNumber);
        self::assertEquals($versionNumber, MigrationsVersion::VERSION());
    }

    public function testIsACustomPharBuild()
    {
        $class  = new \ReflectionClass($this->MigrationVersionClass);
        $method = $class->getMethod('isACustomPharBuild');
        $method->setAccessible(true);

        self::assertFalse($method->invokeArgs(new MigrationsVersion(), ['@git-version@']), 'This is not a custom phar build.');
        self::assertTrue($method->invokeArgs(new MigrationsVersion(), ['v1.0.0-alpha3-125435']), 'This has been replaced by box and is thus a phar build.');
    }
}
