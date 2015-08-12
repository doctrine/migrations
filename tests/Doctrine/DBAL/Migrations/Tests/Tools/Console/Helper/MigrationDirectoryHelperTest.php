<?php
namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\ConfigurationHelper;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\MigrationDirectoryHelper;
use Doctrine\ORM\Configuration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use InvalidArgumentException;

class MigrationDirectoryHelperTest extends MigrationTestCase
{

    public function testMigrationDirectoryHelper()
    {
        $mirationDirectoryHelper = new MigrationDirectoryHelper($this->getSqliteConfiguration());

        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Tools\Console\Helper\MigrationDirectoryHelper', $mirationDirectoryHelper);
    }

    public function testMigrationDirectoryHelperReturnConfiguredDir() {
        $mirationDirectoryHelper = new MigrationDirectoryHelper($this->getSqliteConfiguration());

        $this->assertEquals($this->getSqliteConfiguration()->getMigrationsDirectory(), $mirationDirectoryHelper->getMigrationDirectory());
    }

    public function testMigrationDirectoryHelperReturnConfiguredDirWithYear() {
        $configuration = $this->getSqliteConfiguration();
        $configuration->setMigrationsAreOrganizedByYear(true);
        $mirationDirectoryHelper = new MigrationDirectoryHelper($configuration);

        $dir = $configuration->getMigrationsDirectory() . DIRECTORY_SEPARATOR . date('Y');

        $this->assertEquals($dir, $mirationDirectoryHelper->getMigrationDirectory());
    }

    public function testMigrationDirectoryHelperReturnConfiguredDirWithYearAndMonth() {
        $configuration = $this->getSqliteConfiguration();
        $configuration->setMigrationsAreOrganizedByYearAndMonth(true);
        $mirationDirectoryHelper = new MigrationDirectoryHelper($configuration);

        $dir = $configuration->getMigrationsDirectory() . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m');

        $this->assertEquals($dir, $mirationDirectoryHelper->getMigrationDirectory());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMigrationsDirectoryHelperWithFolderThatDoesNotExists()
    {
        $dir = DIRECTORY_SEPARATOR . 'IDoNotExists';
        $configuration = $this->getSqliteConfiguration();
        $configuration->setMigrationsDirectory($dir);
        $mirationDirectoryHelper = new MigrationDirectoryHelper($configuration);

        $mirationDirectoryHelper->getMigrationDirectory();
    }
}
