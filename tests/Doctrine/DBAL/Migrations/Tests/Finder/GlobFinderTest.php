<?php

namespace Doctrine\DBAL\Migrations\Tests\Finder;

use Doctrine\DBAL\Migrations\Finder\GlobFinder;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;

class GlobFinderTest extends MigrationTestCase
{
    private $finder;

    public function testBadFilenameCausesErrorWhenFindingMigrations()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->finder->findMigrations(__DIR__ . '/does/not/exist/at/all');
    }

    public function testNonDirectoryCausesErrorWhenFindingMigrations()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->finder->findMigrations(__FILE__);
    }

    public function testFindMigrationsReturnsTheExpectedFilesFromDirectory()
    {
        $migrations = $this->finder->findMigrations(__DIR__ . '/_files', 'TestMigrations');

        self::assertArrayHasKey('20150502000000', $migrations);
        self::assertEquals('TestMigrations\\Version20150502000000', $migrations['20150502000000']);
        self::assertArrayHasKey('20150502000001', $migrations);
        self::assertEquals('TestMigrations\\Version20150502000001', $migrations['20150502000001']);
    }

    protected function setUp()
    {
        $this->finder = new GlobFinder();
    }
}
