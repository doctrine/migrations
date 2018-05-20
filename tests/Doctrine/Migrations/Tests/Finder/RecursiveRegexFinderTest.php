<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Finder;

use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use InvalidArgumentException;
use function asort;

class RecursiveRegexFinderTest extends FinderTestCase
{
    public function testVersionNameCausesErrorWhen0() : void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->finder->findMigrations(__DIR__ . '/_regression/NoVersionNamed0');
    }

    public function testBadFilenameCausesErrorWhenFindingMigrations() : void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->finder->findMigrations(__DIR__ . '/does/not/exist/at/all');
    }

    public function testNonDirectoryCausesErrorWhenFindingMigrations() : void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->finder->findMigrations(__FILE__);
    }

    public function testFindMigrationsReturnsTheExpectedFilesFromDirectory() : void
    {
        $migrations = $this->finder->findMigrations(__DIR__ . '/_files');

        self::assertCount(7, $migrations);

        $tests = [
            '20150502000000' => 'TestMigrations\\Version20150502000000',
            '20150502000001' => 'TestMigrations\\Version20150502000001',
            '20150502000003' => 'TestMigrations\\Version20150502000003',
            '20150502000004' => 'TestMigrations\\Version20150502000004',
            '20150502000005' => 'TestMigrations\\Version20150502000005',
            '1ResetVersions' => 'TestMigrations\\Version1ResetVersions',
            '1SymlinkedFile' => 'TestMigrations\\Version1SymlinkedFile',
        ];
        foreach ($tests as $version => $namespace) {
            self::assertArrayHasKey($version, $migrations);
            self::assertEquals($namespace, $migrations[$version]);
        }
        $migrationsForTestSort = (array) $migrations;

        asort($migrationsForTestSort);

        self::assertSame($migrations, $migrationsForTestSort, 'Finder have to return sorted list of the files.');
        self::assertArrayNotHasKey('InvalidVersion20150502000002', $migrations);
        self::assertArrayNotHasKey('Version20150502000002', $migrations);
        self::assertArrayNotHasKey('20150502000002', $migrations);
        self::assertArrayNotHasKey('ADeeperRandomClass', $migrations);
        self::assertArrayNotHasKey('AnotherRandomClassNotStartingWithVersion', $migrations);
        self::assertArrayNotHasKey('ARandomClass', $migrations);
    }

    public function testFindMigrationsCanLocateClassesInNestedNamespacesAndDirectories() : void
    {
        $versions = $this->finder->findMigrations(__DIR__ . '/_features/MultiNamespaceNested');

        $this->assertEquals([
            '0001' => 'TestMigrations\\MultiNested\\Version0001',
            '0002' => 'TestMigrations\\MultiNested\\Deep\\Version0002',
        ], $versions);
    }

    protected function setUp() : void
    {
        $this->finder = new RecursiveRegexFinder();
    }
}
