<?php
namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Helper;

use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\MigrationDirectoryHelper;

class MigrationDirectoryHelperTest extends MigrationTestCase
{

    public function testMigrationDirectoryHelper()
    {
        $mirationDirectoryHelper = new MigrationDirectoryHelper($this->getSqliteConfiguration());

        self::assertInstanceOf(MigrationDirectoryHelper::class, $mirationDirectoryHelper);
    }

    public function testMigrationDirectoryHelperReturnConfiguredDir()
    {
        $mirationDirectoryHelper = new MigrationDirectoryHelper($this->getSqliteConfiguration());

        self::assertEquals($this->getSqliteConfiguration()->getMigrationsDirectory(), $mirationDirectoryHelper->getMigrationDirectory());
    }

    public function testMigrationDirectoryHelperReturnConfiguredDirWithYear()
    {
        $configuration = $this->getSqliteConfiguration();
        $configuration->setMigrationsAreOrganizedByYear(true);
        $mirationDirectoryHelper = new MigrationDirectoryHelper($configuration);

        $dir = $configuration->getMigrationsDirectory() . DIRECTORY_SEPARATOR . date('Y');

        self::assertEquals($dir, $mirationDirectoryHelper->getMigrationDirectory());
    }

    public function testMigrationDirectoryHelperReturnConfiguredDirWithYearAndMonth()
    {
        $configuration = $this->getSqliteConfiguration();
        $configuration->setMigrationsAreOrganizedByYearAndMonth(true);
        $mirationDirectoryHelper = new MigrationDirectoryHelper($configuration);

        $dir = $configuration->getMigrationsDirectory() . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m');

        self::assertEquals($dir, $mirationDirectoryHelper->getMigrationDirectory());
    }

    public function testMigrationsDirectoryHelperWithFolderThatDoesNotExists()
    {
        $dir           = DIRECTORY_SEPARATOR . 'IDoNotExists';
        $configuration = $this->getSqliteConfiguration();
        $configuration->setMigrationsDirectory($dir);
        $mirationDirectoryHelper = new MigrationDirectoryHelper($configuration);

        $this->expectException(\InvalidArgumentException::class);

        $mirationDirectoryHelper->getMigrationDirectory();
    }
}
