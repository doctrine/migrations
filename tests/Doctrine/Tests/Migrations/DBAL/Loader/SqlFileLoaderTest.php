<?php

namespace Doctrine\Tests\Migrations\DBAL\Loader;

use Doctrine\Tests\Migrations\TestCase;
use Doctrine\Migrations\DBAL\Loader\SqlFileLoader;

class SqlFileLoaderTest extends TestCase
{
    /**
     * @test
     */
    public function it_loads_sql_files()
    {
        $loader = new SqlFileLoader();
        list($first, $second) = iterator_to_array($loader->load(__DIR__ . '/_files'));

        $this->assertEquals('1', (string)$first->getVersion());
        $this->assertEquals('1.1', (string)$second->getVersion());

        $this->assertEquals('Test', $first->description);
        $this->assertEquals(__DIR__ . '/_files/V1_Test.sql', $first->script);
        $this->assertEquals('2bdf1665f56c0ce5b966d9c60a7f7eac', $first->checksum);
        $this->assertEquals('sql', $first->type);

        $this->assertEquals('TestPatch', $second->description);
        $this->assertEquals(__DIR__ . '/_files/V1.1_TestPatch.sql', $second->script);
        $this->assertEquals('2bdf1665f56c0ce5b966d9c60a7f7eac', $second->checksum);
        $this->assertEquals('sql', $second->type);
    }
}
