<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Finder;

use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use InvalidArgumentException;

use function count;
use function in_array;
use function sort;

use const PHP_OS_FAMILY;

class RecursiveRegexFinderTest extends FinderTestCase
{
    public function testBadFilenameCausesErrorWhenFindingMigrations(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->finder->findMigrations(__DIR__ . '/does/not/exist/at/all');
    }

    public function testNonDirectoryCausesErrorWhenFindingMigrations(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->finder->findMigrations(__FILE__);
    }

    public function testFindMigrationsReturnsTheExpectedFilesFromDirectory(): void
    {
        $migrations = $this->finder->findMigrations(__DIR__ . '/_files');

        $tests = [
            'TestMigrations\\Version20150502000000',
            'TestMigrations\\Version20150502000001',
            'TestMigrations\\Version20150502000003',
            'TestMigrations\\Version20150502000004',
            'TestMigrations\\Version20150502000005',
            'TestMigrations\\Version1ResetVersions',
            'TestMigrations\\DifferentNamingSchema',
        ];

        if (PHP_OS_FAMILY !== 'Windows') {
            $tests[] = 'TestMigrations\\Version1SymlinkedFile';
        }

        self::assertCount(count($tests), $migrations); // Windows does not support symlinks
        foreach ($tests as $fqcn) {
            self::assertTrue(in_array($fqcn, $migrations, true));
        }

        self::assertArrayNotHasKey('InvalidVersion20150502000002', $migrations);
        self::assertArrayNotHasKey('Version20150502000002', $migrations);
        self::assertArrayNotHasKey('20150502000002', $migrations);
        self::assertArrayNotHasKey('ADeeperRandomClass', $migrations);
        self::assertArrayNotHasKey('AnotherRandomClassNotStartingWithVersion', $migrations);
        self::assertArrayNotHasKey('ARandomClass', $migrations);
    }

    public function testFindMigrationsCanLocateClassesInNestedNamespacesAndDirectories(): void
    {
        $versions = $this->finder->findMigrations(__DIR__ . '/_features/MultiNamespaceNested');

        $expectedVersions = [
            'TestMigrations\\MultiNested\\Version0001',
            'TestMigrations\\MultiNested\\Deep\\Version0002',
        ];

        sort($expectedVersions);
        sort($versions);

        self::assertSame($expectedVersions, $versions);
    }

    public function testMigrationsInSubnamespaceAreLoadedIfNamespaceIsParentNamespace(): void
    {
        $versions = $this->finder->findMigrations(
            __DIR__ . '/_features/MultiNamespaceNested',
            'TestMigrations\\MultiNested'
        );

        $expectedVersions = [
            'TestMigrations\MultiNested\Version0001',
            'TestMigrations\MultiNested\Deep\Version0002',
        ];

        sort($expectedVersions);
        sort($versions);

        self::assertSame($expectedVersions, $versions);
    }

    public function testOnlyMigrationsInTheProvidedNamespacesAreLoadedIfNamespaceIsProvided(): void
    {
        $versions = $this->finder->findMigrations(
            __DIR__ . '/_features/MultiNamespaceNested',
            'TestMigrations\\MultiNested\\Deep'
        );

        self::assertSame(['TestMigrations\MultiNested\Deep\Version0002'], $versions);
    }

    protected function setUp(): void
    {
        $this->finder = new RecursiveRegexFinder();
    }
}
