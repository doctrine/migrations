<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Finder;

use Doctrine\Migrations\Finder\GlobFinder;
use InvalidArgumentException;

class GlobFinderTest extends FinderTestCase
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

        self::assertContains('TestMigrations\\Version20150502000000', $migrations);
        self::assertContains('TestMigrations\\Version20150502000001', $migrations);
    }

    protected function setUp(): void
    {
        $this->finder = new GlobFinder();
    }
}
