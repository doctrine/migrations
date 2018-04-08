<?php
namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Configuration\ArrayConfiguration;
use Doctrine\DBAL\Migrations\Configuration\JsonConfiguration;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\ConfigurationHelper;
use Doctrine\ORM\Configuration;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigurationHelperTest extends MigrationTestCase
{

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var OutputWriter
     */
    protected $outputWriter;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var InputInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $input;

    protected function setUp()
    {
        $this->configuration = $this->getSqliteConfiguration();

        $this->connection = $this->getSqliteConnection();

        $this->input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->setMethods(['getOption'])
            ->getMock();
    }

    public function testConfigurationHelper()
    {
        $configurationHelper = new ConfigurationHelper($this->connection, $this->configuration);

        self::assertInstanceOf(ConfigurationHelper::class, $configurationHelper);
    }

    //used in other tests to see if xml or yaml or yml config files are loaded.
    protected function getConfigurationHelperLoadsASpecificFormat($baseFile, $configFile)
    {
        try {
            $file = 'tests/Doctrine/DBAL/Migrations/Tests/Tools/Console/Helper/files/' . $baseFile;
            copy($file, $configFile);

            $this->input->method('getOption')
                ->with('configuration')
                ->will($this->returnValue(null));

            $configurationHelper = new ConfigurationHelper($this->getSqliteConnection());
            $configfileLoaded    = $configurationHelper->getMigrationConfig($this->input, $this->getOutputWriter());

            self::assertAttributeSame($this->getOutputWriter(), 'outputWriter', $configfileLoaded);

            return trim($this->getOutputStreamContent($this->output));
        } finally {
            unlink($configFile); //i want to be really sure to cleanup this file
        }
    }

    public function testConfigurationHelperLoadsXmlFormat()
    {
        self::assertStringMatchesFormat(
            'Loading configuration from file: migrations.xml',
            $this->getConfigurationHelperLoadsASpecificFormat('config.xml', 'migrations.xml')
        );
    }

    public function testConfigurationHelperLoadsYamlFormat()
    {
        self::assertStringMatchesFormat(
            'Loading configuration from file: migrations.yaml',
            $this->getConfigurationHelperLoadsASpecificFormat('config.yml', 'migrations.yaml')
        );
    }

    public function testConfigurationHelperLoadsYmlFormat()
    {
        self::assertStringMatchesFormat(
            'Loading configuration from file: migrations.yml',
            $this->getConfigurationHelperLoadsASpecificFormat('config.yml', 'migrations.yml')
        );
    }

    public function testConfigurationHelperLoadsJsonFormat()
    {
        self::assertStringMatchesFormat(
            'Loading configuration from file: migrations.json',
            $this->getConfigurationHelperLoadsASpecificFormat('config.json', 'migrations.json')
        );
    }

    public function testConfigurationHelperLoadsPhpFormat()
    {
        self::assertStringMatchesFormat(
            'Loading configuration from file: migrations.php',
            $this->getConfigurationHelperLoadsASpecificFormat('config.php', 'migrations.php')
        );
    }

    public function testConfigurationHelperLoadsPhpArrayFormatFromCommandLine()
    {
        $this->input->method('getOption')
            ->with('configuration')
            ->will($this->returnValue(__DIR__ . '/files/config.php'));

        $configurationHelper = new ConfigurationHelper($this->getSqliteConnection());
        $migrationConfig     = $configurationHelper->getMigrationConfig($this->input, $this->getOutputWriter());

        self::assertInstanceOf(ArrayConfiguration::class, $migrationConfig);
        self::assertAttributeSame($this->getOutputWriter(), 'outputWriter', $migrationConfig);
        self::assertSame('DoctrineMigrationsTest', $migrationConfig->getMigrationsNamespace());
    }

    public function testConfigurationHelperLoadsJsonFormatFromCommandLine()
    {
        $this->input->method('getOption')
            ->with('configuration')
            ->will($this->returnValue(__DIR__ . '/files/config.json'));

        $configurationHelper = new ConfigurationHelper($this->getSqliteConnection());
        $migrationConfig     = $configurationHelper->getMigrationConfig($this->input, $this->getOutputWriter());

        self::assertInstanceOf(JsonConfiguration::class, $migrationConfig);
        self::assertAttributeSame($this->getOutputWriter(), 'outputWriter', $migrationConfig);
        self::assertSame('DoctrineMigrationsTest', $migrationConfig->getMigrationsNamespace());
    }

    /**
     * Test that unsupported file type throws exception
     */
    public function testConfigurationHelperFailsToLoadOtherFormat()
    {
        $this->input->method('getOption')
            ->with('configuration')
            ->will($this->returnValue('testconfig.wrong'));

        $configurationHelper = new ConfigurationHelper($this->getSqliteConnection());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Given config file type is not supported');

        $configurationHelper->getMigrationConfig($this->input, $this->getOutputWriter());
    }

    public function testConfigurationHelperWithConfigurationFromSetter()
    {
        $this->input->method('getOption')
            ->with('configuration')
            ->will($this->returnValue(null));

        $configurationHelper = new ConfigurationHelper($this->connection, $this->configuration);

        $migrationConfig = $configurationHelper->getMigrationConfig($this->input, $this->getOutputWriter());

        self::assertInstanceOf(\Doctrine\DBAL\Migrations\Configuration\Configuration::class, $migrationConfig);
        self::assertAttributeSame($this->getOutputWriter(), 'outputWriter', $migrationConfig);
        self::assertStringMatchesFormat("Loading configuration from the integration code of your framework (setter).", trim($this->getOutputStreamContent($this->output)));
    }

    public function testConfigurationHelperWithConfigurationFromSetterAndOverrideFromCommandLine()
    {
        $this->input->method('getOption')
            ->with('configuration')
            ->will($this->returnValue(__DIR__ . '/../Command/_files/config.yml'));

        $configurationHelper = new ConfigurationHelper($this->connection, $this->configuration);

        $migrationConfig = $configurationHelper->getMigrationConfig($this->input, $this->getOutputWriter());

        self::assertInstanceOf(\Doctrine\DBAL\Migrations\Configuration\Configuration::class, $migrationConfig);
        self::assertAttributeSame($this->getOutputWriter(), 'outputWriter', $migrationConfig);
        self::assertStringMatchesFormat("Loading configuration from command option: %a", $this->getOutputStreamContent($this->output));
    }

    public function testConfigurationHelperWithoutConfigurationFromSetterAndWithoutOverrideFromCommandLineAndWithoutConfigInPath()
    {
        $this->input->method('getOption')
            ->with('configuration')
            ->will($this->returnValue(null));

        $configurationHelper = new ConfigurationHelper($this->connection, null);

        $migrationConfig = $configurationHelper->getMigrationConfig($this->input, $this->getOutputWriter());

        self::assertInstanceOf(\Doctrine\DBAL\Migrations\Configuration\Configuration::class, $migrationConfig);
        self::assertAttributeSame($this->getOutputWriter(), 'outputWriter', $migrationConfig);
        self::assertStringMatchesFormat("", $this->getOutputStreamContent($this->output));
    }
}
