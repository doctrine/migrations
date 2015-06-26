<?php
namespace Doctrine\DBAL\Migrations\Tests;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Migration;
use \Mockery as m;

class MigrationTest extends MigrationTestCase
{
    /** @var Configuration */
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

        $this->setExpectedException(
            'Doctrine\DBAL\Migrations\MigrationException',
            'Could not find migration version 1234'
        );
        $migration->migrate('1234');
    }

    /**
     * @expectedException \Doctrine\DBAL\Migrations\MigrationException
     * @expectedExceptionMessage Could not find any migrations to execute.
     */
    public function testMigrateWithNoMigrationsThrowsException()
    {
        $migration = new Migration($this->config);

        $migration->migrate();
    }

    /**
     * @param $to
     *
     * @dataProvider getSqlProvider
     */
    public function testGetSql($to)
    {
        $migrationMock = m::mock('Doctrine\DBAL\Migrations\Migration');
        $migrationMock->makePartial();
        $expected = 'something';
        $migrationMock->shouldReceive('migrate')->with($to, true)->andReturn($expected);
        $result = $migrationMock->getSql($to);
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for ::testGetSql()
     * @return array
     */
    public function getSqlProvider()
    {
        return [
            [null],
            ['test'],
        ];
    }

    /**
     * @param $path
     * @param $to
     * @param $getSqlReturn
     *
     * @dataProvider writeSqlFileProvider
     */
    public function testWriteSqlFile($path, $from, $to, $getSqlReturn)
    {
        $outputWriter = m::mock('Doctrine\DBAL\Migrations\OutputWriter');
        $outputWriter->shouldReceive('write');

        $config = m::mock('Doctrine\DBAL\Migrations\Configuration\Configuration')
            ->makePartial();
        $config->shouldReceive('getCurrentVersion')->andReturn($from);
        $config->shouldReceive('getOutputWriter')->andReturn($outputWriter);
        if ($to == null) { // this will always just test the "up" direction
            $config->shouldReceive('getLatestVersion')->andReturn($from + 1);
        }

        $migration = m::mock('Doctrine\DBAL\Migrations\Migration[getSql]', [$config])->makePartial();
        $migration->shouldReceive('getSql')->with($to)->andReturn($getSqlReturn);

        $result = $migration->writeSqlFile($path, $to);
        $this->assertNotFalse($result);
        if (!is_dir($path)) {
            $this->assertNotEmpty(file_get_contents($path));
        }

        // cleanup if necessary
        if (is_dir($path)) {
            $createdFiles = glob(realpath($path) . '/*.sql');
            foreach($createdFiles as $file) {
                unlink($file);
            }
        } elseif(is_file($path)) {
            unlink($path);
        }
    }

    public function writeSqlFileProvider()
    {
        return [
            [__DIR__, 0, 1, ['1' => ['SHOW DATABASES;']]], // up
            [__DIR__, 1, 1, ['1' => ['SHOW DATABASES;']]], // up (same)
            [__DIR__, 1, 0, ['1' => ['SHOW DATABASES;']]], // down
            [__DIR__ . '/tmpfile.sql', 0, 1, ['1' => ['SHOW DATABASES']]], // tests something actually got written
        ];
    }

}
