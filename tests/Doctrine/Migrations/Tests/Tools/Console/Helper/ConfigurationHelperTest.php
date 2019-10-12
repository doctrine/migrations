<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Helper;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\ConfigurationLoader;
use Doctrine\Migrations\Configuration\Exception\UnknownLoader;
use Doctrine\Migrations\Configuration\Loader\Loader;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Helper\ConfigurationHelper;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use function chdir;
use function getcwd;

class ConfigurationHelperTest extends MigrationTestCase
{
    /** @var StreamOutput */
    protected $output;

    /** @var InputInterface|MockObject */
    private $input;

    /** @var MockObject */
    private $loader;

    /** @var ConfigurationHelper */
    private $configurationHelper;

    protected function setUp() : void
    {
        $this->input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->setMethods(['getOption'])
            ->getMock();

        $this->loader              = $this->createMock(ConfigurationLoader::class);
        $this->configurationHelper = new ConfigurationHelper($this->loader);
    }

    /**
     * Test that unsupported file type throws exception
     */
    public function testNoAvailableConfigGivesBackEmptyConfig() : void
    {
        $this->input->method('getOption')
            ->with('configuration')
            ->willReturn(null);

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
            $this->configurationHelper->getConfiguration($this->input);
        } finally {
            chdir($dir);
        }
    }

    public function testConfigurationHelperFailsToLoadOtherFormat() : void
    {
        $this->input->method('getOption')
            ->with('configuration')
            ->will(self::returnValue('testconfig.wrong'));
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
            $this->configurationHelper->getConfiguration($this->input);
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

        $this->input->method('getOption')
            ->with('configuration')
            ->will(self::returnValue('config.php'));

        $config = $this->configurationHelper->getConfiguration($this->input);

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

        $this->input->method('getOption')
            ->with('configuration')
            ->willReturn(null);

        $dir = getcwd()?: '.';
        try {
            chdir(__DIR__ . '/files');
            $config = $this->configurationHelper->getConfiguration($this->input);
        } finally {
            chdir($dir);
        }
        self::assertSame($config, $confExpected);
    }
}
