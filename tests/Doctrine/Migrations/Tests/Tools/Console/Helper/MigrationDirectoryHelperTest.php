<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Helper;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Helper\MigrationDirectoryHelper;
use InvalidArgumentException;
use const DIRECTORY_SEPARATOR;
use function date;
use function sys_get_temp_dir;

class MigrationDirectoryHelperTest extends MigrationTestCase
{
    /** @var MigrationDirectoryHelper */
    private $mirationDirectoryHelper;

    /** @var Configuration */
    private $configuration;

    public function setUp() : void
    {
        $this->mirationDirectoryHelper = new MigrationDirectoryHelper();
        $this->configuration           = new Configuration();
        $this->configuration->addMigrationsDirectory('DoctrineMigrations', sys_get_temp_dir());
    }

    public function testMigrationDirectoryHelperReturnConfiguredDir() : void
    {
        foreach ($this->configuration->getMigrationDirectories() as $dir) {
            $migrationDir = $this->mirationDirectoryHelper->getMigrationDirectory($this->configuration, $dir);
            self::assertSame($dir, $migrationDir);
        }
    }

    public function testMigrationDirectoryHelperReturnConfiguredDirWithYear() : void
    {
        $this->configuration->setMigrationsAreOrganizedByYear(true);

        foreach ($this->configuration->getMigrationDirectories() as $dir) {
            $migrationDir = $this->mirationDirectoryHelper->getMigrationDirectory($this->configuration, $dir);
            $expectedDir  = $dir . DIRECTORY_SEPARATOR . date('Y');

            self::assertSame($expectedDir, $migrationDir);
        }
    }

    public function testMigrationDirectoryHelperReturnConfiguredDirWithYearAndMonth() : void
    {
        $this->configuration->setMigrationsAreOrganizedByYearAndMonth(true);

        foreach ($this->configuration->getMigrationDirectories() as $dir) {
            $migrationDir = $this->mirationDirectoryHelper->getMigrationDirectory($this->configuration, $dir);
            $expectedDir  = $dir . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m');

            self::assertSame($expectedDir, $migrationDir);
        }
    }

    public function testMigrationsDirectoryHelperWithFolderThatDoesNotExists() : void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->configuration->addMigrationsDirectory('DoctrineMigrations', '/non_exiting_folder');

        foreach ($this->configuration->getMigrationDirectories() as $dir) {
            $this->mirationDirectoryHelper->getMigrationDirectory($this->configuration, $dir);
        }
    }
}
