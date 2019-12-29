<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\ConfigurationFormatLoader;
use Doctrine\Migrations\Configuration\ConfigurationLoader;
use Doctrine\Migrations\Configuration\Exception\UnknownLoader;
use Doctrine\Migrations\Configuration\Loader\Loader;
use Doctrine\Migrations\Tests\MigrationTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use function chdir;
use function getcwd;

class ConfigurationLoaderTest extends MigrationTestCase
{
    /** @var MockObject */
    private $loader;

    /** @var ConfigurationLoader */
    private $configurationLoader;

    protected function setUp() : void
    {
        $this->loader              = $this->createMock(ConfigurationFormatLoader::class);
        $this->configurationLoader = new ConfigurationLoader($this->loader);
    }

    /**
     * Test that unsupported file type throws exception
     */
    public function testNoAvailableConfigGivesBackEmptyConfig() : void
    {
        $confExpected = new Configuration();

        $configLoader = $this->createMock(Loader::class);

        $configLoader
            ->expects(self::once())
            ->method('load')
            ->with([])
            ->willReturn($confExpected);

        $this->loader
            ->expects(self::once())
            ->method('getLoader')
            ->with('array')
            ->willReturn($configLoader);

        $dir = getcwd()?: '.';
        try {
            chdir(__DIR__);
            $this->configurationLoader->getConfiguration(null);
        } finally {
            chdir($dir);
        }
    }

    public function testConfigurationLoaderFailsToLoadOtherFormat() : void
    {
        $this->loader
            ->expects(self::once())
            ->method('getLoader')
            ->with('wrong')
            ->willThrowException(UnknownLoader::new('dummy'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Given config file type is not supported');

        $dir = getcwd()?: '.';
        try {
            chdir(__DIR__);
            $this->configurationLoader->getConfiguration('testconfig.wrong');
        } finally {
            chdir($dir);
        }
    }

    public function testLoadsFile() : void
    {
        $confExpected = new Configuration();
        $configLoader = $this->createMock(Loader::class);

        $configLoader
            ->expects(self::once())
            ->method('load')
            ->with('config.php')
            ->willReturn($confExpected);

        $this->loader
            ->expects(self::once())
            ->method('getLoader')
            ->with('php')
            ->willReturn($configLoader);

        $config = $this->configurationLoader->getConfiguration('config.php');

        self::assertSame($config, $confExpected);
    }

    public function testLoadsDefaultFile() : void
    {
        $confExpected = new Configuration();
        $configLoader = $this->createMock(Loader::class);

        $configLoader
            ->expects(self::once())
            ->method('load')
            ->with('migrations.php')
            ->willReturn($confExpected);

        $this->loader
            ->expects(self::once())
            ->method('getLoader')
            ->with('php')
            ->willReturn($configLoader);

        $dir = getcwd()?: '.';
        try {
            chdir(__DIR__ . '/_files_loader');
            $config = $this->configurationLoader->getConfiguration(null);
        } finally {
            chdir($dir);
        }
        self::assertSame($config, $confExpected);
    }
}
