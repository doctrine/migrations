<?php

namespace Doctrine\DBAL\Migrations\Tests;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Migration;
use \Mockery as m;

class MigrationTest extends MigrationTestCase
{
    private $config;

    public function setUp()
    {
        $this->config = new Configuration($this->getSqliteConnection());
        $this->config->setMigrationsDirectory(\sys_get_temp_dir());
        $this->config->setMigrationsNamespace('DoctrineMigrations\\');
    }

    public function testMigrateToUnknownVersionThrowsException()
    {
        $migration = new Migration($this->config);

        $this->setExpectedException('Doctrine\DBAL\Migrations\MigrationException', 'Could not find migration version 1234');
        $migration->migrate('1234');
    }

    /**
     * @expectedException \Doctrine\DBAL\Migrations\MigrationException
     * @expectedExceptionMessage Could not find any migrations to execute.
     */
    public function testMigrateWithNoMigrationsThrowsException()
    {
        $migration = new Migration($this->config);

        $sql = $migration->migrate();
    }

    /**
     * @param $to
     *
     * @dataProvider getSqlProvider
     */
    public function testGetSql($to)
    {
        $migrationMock = m::mock('Doctrine\DBAL\Migrations\Migration')->makePartial();
        $migrationMock->makePartial();
        $expected = 'something';
        $migrationMock->shouldReceive('migrate')->with($to, true)->andReturn($expected);
        $result = $migrationMock->getSql($to);
        $this->assertEquals($expected, $result);
    }

    public function getSqlProvider()
    {
        return [
            [null],
            ['test'],
        ];
    }

}
