<?php

namespace Doctrine\Tests\Migrations;

use Doctrine\Migrations\MigrationSet;
use Doctrine\Migrations\Version;

class MigrationSetTest extends TestCase
{
    /**
     * @test
     */
    public function it_allows_retrievial_by_version()
    {
        $migration = $this->createMigrationInfo('1');
        $set = new MigrationSet(array($migration));

        $this->assertSame($migration, $set->get($migration->getVersion()));
    }

    /**
     * @test
     */
    public function it_fails_accessing_unknown_version()
    {
        $this->setExpectedException('OutOfBoundsException');

        $set = new MigrationSet();
        $set->get(new Version('1'));
    }

    /**
     * @test
     */
    public function it_can_check_for_contained_versions()
    {
        $migration = $this->createMigrationInfo('1');
        $set = new MigrationSet(array($migration));

        $this->assertTrue($set->contains(new Version('1')));
        $this->assertFalse($set->contains(new Version('2')));
    }

    /**
     * @test
     */
    public function it_iteraters_over_versions_in_order()
    {
        $set = new MigrationSet(array(
            $migration3 = $this->createMigrationInfo('3'),
            $migration1 = $this->createMigrationInfo('1'),
            $migration2dot1 = $this->createMigrationInfo('2.1'),
            $migration2 = $this->createMigrationInfo('2'),
        ));

        $migrations = iterator_to_array($set);

        $this->assertEquals(
            array($migration1, $migration2, $migration2dot1, $migration3),
            $migrations
        );
    }

    /**
     * @test
     */
    public function it_prevents_adding_duplicates()
    {
        $migration = $this->createMigrationInfo('1');
        $set = new MigrationSet(array($migration));

        $this->setExpectedException('RuntimeException');
        $set->add($migration);
    }
}
