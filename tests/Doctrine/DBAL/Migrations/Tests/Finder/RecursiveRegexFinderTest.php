<?php

namespace Doctrine\DBAL\Migrations\Tests\Finder;

use Doctrine\DBAL\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;

class RecursiveRegexFinderTest extends MigrationTestCase
{
    private $finder;

    public function testVersionNameCausesErrorWhen0()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->finder->findMigrations(__DIR__ . '/_regression/NoVersionNamed0');
    }

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

        self::assertCount(7, $migrations);

        $tests = [
            '20150502000000' => 'TestMigrations\\Version20150502000000',
            '20150502000001' => 'TestMigrations\\Version20150502000001',
            '20150502000003' => 'TestMigrations\\Version20150502000003',
            '20150502000004' => 'TestMigrations\\Version20150502000004',
            '20150502000005' => 'TestMigrations\\Version20150502000005',
            '1_reset_versions' => 'TestMigrations\\Version1_reset_versions',
            '1_symlinked_file' => 'TestMigrations\\Version1_symlinked_file',
        ];
        foreach ($tests as $version => $namespace) {
            self::assertArrayHasKey($version, $migrations);
            self::assertEquals($namespace, $migrations[$version]);
        }
        $migrationsForTestSort = (array) $migrations;

        asort($migrationsForTestSort);

        self::assertTrue($migrationsForTestSort === $migrations, "Finder have to return sorted list of the files.");
        self::assertArrayNotHasKey('InvalidVersion20150502000002', $migrations);
        self::assertArrayNotHasKey('Version20150502000002', $migrations);
        self::assertArrayNotHasKey('20150502000002', $migrations);
        self::assertArrayNotHasKey('ADeeperRandomClass', $migrations);
        self::assertArrayNotHasKey('AnotherRandomClassNotStartingWithVersion', $migrations);
        self::assertArrayNotHasKey('ARandomClass', $migrations);
    }

    protected function setUp()
    {
        $this->finder = new RecursiveRegexFinder();
    }
}
