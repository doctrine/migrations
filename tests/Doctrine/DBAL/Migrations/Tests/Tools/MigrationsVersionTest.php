<?php
namespace Doctrine\DBAL\Migrations\Tests\Tools;

use Doctrine\DBAL\Migrations\MigrationsVersion;

class MigrationsVersionTest extends \PHPUnit_Framework_TestCase {

    private $MigrationVersionClass = 'Doctrine\DBAL\Migrations\MigrationsVersion';

    public function testVersionNumber() {
        $class = new \ReflectionClass($this->MigrationVersionClass);
        $property = $class->getProperty('version');
        $property->setAccessible(true);

        $versionNumber = $property->getValue(new MigrationsVersion());
        $this->assertRegExp('/^v[0-9]{1,}\.[0-9]{1,}\.[0-9]{1,}(-([a-z]){1,}[0-9]{1,}){0,1}$/', $versionNumber);
        $this->assertEquals($versionNumber, MigrationsVersion::VERSION());
    }

    public function testIsACustomPharBuild(){
        $class = new \ReflectionClass($this->MigrationVersionClass);
        $method = $class->getMethod('isACustomPharBuild');
        $method->setAccessible(true);

        $this->assertFalse($method->invokeArgs(new MigrationsVersion(), array('@git-version@')), 'This is not a custom phar build.');
        $this->assertTrue($method->invokeArgs(new MigrationsVersion(), array('v1.0.0-alpha3-125435')), 'This has been replaced by box and is thus a phar build.');
    }

}