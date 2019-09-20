<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Helper;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Helper\MigrationDirectoryHelper;
use InvalidArgumentException;
use const DIRECTORY_SEPARATOR;
use function closedir;
use function date;
use function is_dir;
use function mkdir;
use function opendir;
use function readdir;
use function rmdir;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

class MigrationDirectoryHelperTest extends MigrationTestCase
{
    /** @var MigrationDirectoryHelper */
    private $mirationDirectoryHelper;

    /** @var Configuration */
    private $configuration;

    /** @var string */
    private $tempDir;

    public function setUp() : void
    {
        $this->mirationDirectoryHelper = new MigrationDirectoryHelper();
        $this->configuration           = new Configuration();
        $this->tempDir                 = tempnam(sys_get_temp_dir(), 'DoctrineMigrations-tests');
        @unlink($this->tempDir);
        mkdir($this->tempDir);
        $this->configuration->addMigrationsDirectory('DoctrineMigrations', $this->tempDir);
    }

    public function tearDown() : void
    {
        rrmdir($this->tempDir);
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

function rrmdir(string $src) : void
{
    $dir = opendir($src);
    while (( $file = readdir($dir)) !== false) {
        if (( $file === '.' ) || ( $file === '..' )) {
            continue;
        }

        $full = $src . '/' . $file;
        if (is_dir($full)) {
            rrmdir($full);
        } else {
            unlink($full);
        }
    }
    closedir($dir);
    rmdir($src);
}
