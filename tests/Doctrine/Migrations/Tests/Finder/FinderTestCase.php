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
        $versions = $this->finder->findMigrations(__DIR__ . '/_features/MultiNamespace');

        $this->assertEquals([
            '0001' => 'TestMigrations\\Test\\Version0001',
            '0002' => 'TestMigrations\\TestOther\\Version0002',
        ], $versions);
    }
}
