<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\ArrayConfiguration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\JsonConfiguration;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Helper\ConfigurationHelper;
use Doctrine\ORM\Configuration as ORMConfiguration;
use InvalidArgumentException;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function copy;
use function trim;
use function unlink;

class ConfigurationHelperTest extends MigrationTestCase
{
    /** @var Connection */
    private $connection;

    /** @var ORMConfiguration */
    private $configuration;

    /** @var OutputWriter */
    protected $outputWriter;

    /** @var OutputInterface */
    protected $output;

    /** @var InputInterface|PHPUnit_Framework_MockObject_MockObject */
    private $input;

    protected function setUp() : void
    {
        $this->configuration = $this->getSqliteConfiguration();

        $this->connection = $this->getSqliteConnection();

        $this->output = $this->getOutputStream();

        $this->input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->setMethods(['getOption'])
            ->getMock();
    }

    public function testConfigurationHelper() : void
    {
        $configurationHelper = new ConfigurationHelper($this->connection, $this->configuration);

        self::assertInstanceOf(ConfigurationHelper::class, $configurationHelper);
    }

    /**
     * Used in other tests to see if xml or yaml or yml config files are loaded.
     */
    protected function getConfigurationHelperLoadsASpecificFormat(
        string $baseFile,
        string $configFile
    ) : string {
        try {
            $file = 'tests/Doctrine/Migrations/Tests/Tools/Console/Helper/files/' . $baseFile;
            copy($file, $configFile);

            $this->input->method('getOption')
                ->with('configuration')
                ->will(self::returnValue(null));

            $configurationHelper = new ConfigurationHelper($this->getSqliteConnection());
            $configfileLoaded    = $configurationHelper->getMigrationConfig($this->input);

            return trim($this->getOutputStreamContent($this->output));
        } finally {
            unlink($configFile); //i want to be really sure to cleanup this file
        }
    }

    public function testConfigurationHelperLoadsPhpArrayFormatFromCommandLine() : void
    {
        $this->input->method('getOption')
            ->with('configuration')
            ->will(self::returnValue(__DIR__ . '/files/config.php'));

        $configurationHelper = new ConfigurationHelper($this->getSqliteConnection());
        $migrationConfig     = $configurationHelper->getMigrationConfig($this->input);

        self::assertInstanceOf(ArrayConfiguration::class, $migrationConfig);
        self::assertSame('DoctrineMigrationsTest', $migrationConfig->getMigrationsNamespace());
    }

    public function testConfigurationHelperLoadsJsonFormatFromCommandLine() : void
    {
        $this->input->method('getOption')
            ->with('configuration')
            ->will(self::returnValue(__DIR__ . '/files/config.json'));

        $configurationHelper = new ConfigurationHelper($this->getSqliteConnection());
        $migrationConfig     = $configurationHelper->getMigrationConfig($this->input);

        self::assertInstanceOf(JsonConfiguration::class, $migrationConfig);
        self::assertSame('DoctrineMigrationsTest', $migrationConfig->getMigrationsNamespace());
    }

    /**
     * Test that unsupported file type throws exception
     */
    public function testConfigurationHelperFailsToLoadOtherFormat() : void
    {
        $this->input->method('getOption')
            ->with('configuration')
            ->will(self::returnValue('testconfig.wrong'));

        $configurationHelper = new ConfigurationHelper($this->getSqliteConnection());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Given config file type is not supported');

        $configurationHelper->getMigrationConfig($this->input);
    }

    public function testConfigurationHelperWithoutConfigurationFromSetterAndWithoutOverrideFromCommandLineAndWithoutConfigInPath() : void
    {
        $this->input->method('getOption')
            ->with('configuration')
            ->will(self::returnValue(null));

        $configurationHelper = new ConfigurationHelper($this->connection, null);

        $migrationConfig = $configurationHelper->getMigrationConfig($this->input);

        self::assertInstanceOf(Configuration::class, $migrationConfig);
        self::assertStringMatchesFormat('', $this->getOutputStreamContent($this->output));
    }
}
