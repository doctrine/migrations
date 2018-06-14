<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Helper;

use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Helper\MigrationDirectoryHelper;
use InvalidArgumentException;
use const DIRECTORY_SEPARATOR;
use function date;

class MigrationDirectoryHelperTest extends MigrationTestCase
{
    public function testMigrationDirectoryHelper() : void
    {
        $mirationDirectoryHelper = new MigrationDirectoryHelper($this->getSqliteConfiguration());

        self::assertInstanceOf(MigrationDirectoryHelper::class, $mirationDirectoryHelper);
    }

    public function testMigrationDirectoryHelperReturnConfiguredDir() : void
    {
        $mirationDirectoryHelper = new MigrationDirectoryHelper($this->getSqliteConfiguration());

        self::assertSame($this->getSqliteConfiguration()->getMigrationsDirectory(), $mirationDirectoryHelper->getMigrationDirectory());
    }

    public function testMigrationDirectoryHelperReturnConfiguredDirWithYear() : void
    {
        $configuration = $this->getSqliteConfiguration();
        $configuration->setMigrationsAreOrganizedByYear(true);
        $mirationDirectoryHelper = new MigrationDirectoryHelper($configuration);

        $dir = $configuration->getMigrationsDirectory() . DIRECTORY_SEPARATOR . date('Y');

        self::assertSame($dir, $mirationDirectoryHelper->getMigrationDirectory());
    }

    public function testMigrationDirectoryHelperReturnConfiguredDirWithYearAndMonth() : void
    {
        $configuration = $this->getSqliteConfiguration();
        $configuration->setMigrationsAreOrganizedByYearAndMonth(true);
        $mirationDirectoryHelper = new MigrationDirectoryHelper($configuration);

        $dir = $configuration->getMigrationsDirectory() . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m');

        self::assertSame($dir, $mirationDirectoryHelper->getMigrationDirectory());
    }

    public function testMigrationsDirectoryHelperWithFolderThatDoesNotExists() : void
    {
        $dir           = DIRECTORY_SEPARATOR . 'IDoNotExists';
        $configuration = $this->getSqliteConfiguration();
        $configuration->setMigrationsDirectory($dir);
        $mirationDirectoryHelper = new MigrationDirectoryHelper($configuration);

        $this->expectException(InvalidArgumentException::class);

        $mirationDirectoryHelper->getMigrationDirectory();
    }
}
