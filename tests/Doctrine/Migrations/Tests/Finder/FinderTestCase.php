<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Finder;

use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\Tests\MigrationTestCase;

abstract class FinderTestCase extends MigrationTestCase
{
    /** @var MigrationFinder */
    protected $finder;

    public function testClassesInMultipleNamespacesCanBeLoadedByTheFinder() : void
    {
        $versions = $this->finder->findMigrations(__DIR__ . '/_features/MultiNamespace', 'TestMigrations');

        self::assertArraySubset([
            'TestMigrations\\Test\\Version0001',
            'TestMigrations\\TestOther\\Version0002',
        ], $versions);
    }

    public function testOnlyClassesInTheProvidedNamespaceAreLoadedWhenNamespaceIsProvided() : void
    {
        $versions = $this->finder->findMigrations(
            __DIR__ . '/_features/MultiNamespace',
            'TestMigrations\\Test'
        );

        self::assertSame(['TestMigrations\\Test\\Version0001'], $versions);
    }
}
