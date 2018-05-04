<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Configuration\ArrayConfiguration;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Configuration\JsonConfiguration;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\ConfigurationHelper;
use Doctrine\ORM\Configuration as ORMConfiguration;
use InvalidArgumentException;
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

    /** @var InputInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $input;

    protected function setUp() : void
    {
        $this->configuration = $this->getSqliteConfiguration();

        $this->connection = $this->getSqliteConnection();

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

    public function testConfigurationHelperLoadsXmlFormat() : void
    {
        self::assertStringMatchesFormat(
            'Loading configuration from file: migrations.xml',
            $this->getConfigurationHelperLoadsASpecificFormat('config.xml', 'migrations.xml')
        );
    }

    public function testConfigurationHelperLoadsYamlFormat() : void
    {
        self::assertStringMatchesFormat(
            'Loading configuration from file: migrations.yaml',
            $this->getConfigurationHelperLoadsASpecificFormat('config.yml', 'migrations.yaml')
        );
    }

    public function testConfigurationHelperLoadsYmlFormat() : void
    {
        self::assertStringMatchesFormat(
            'Loading configuration from file: migrations.yml',
            $this->getConfigurationHelperLoadsASpecificFormat('config.yml', 'migrations.yml')
        );
    }

    public function testConfigurationHelperLoadsJsonFormat() : void
    {
        self::assertStringMatchesFormat(
            'Loading configuration from file: migrations.json',
            $this->getConfigurationHelperLoadsASpecificFormat('config.json', 'migrations.json')
        );
    }

    public function testConfigurationHelperLoadsPhpFormat() : void
    {
        self::assertStringMatchesFormat(
            'Loading configuration from file: migrations.php',
            $this->getConfigurationHelperLoadsASpecificFormat('config.php', 'migrations.php')
        );
    }

    public function testConfigurationHelperLoadsPhpArrayFormatFromCommandLine() : void
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

    public function testConfigurationHelperLoadsJsonFormatFromCommandLine() : void
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
    public function testConfigurationHelperFailsToLoadOtherFormat() : void
    {
        $this->input->method('getOption')
            ->with('configuration')
            ->will($this->returnValue('testconfig.wrong'));

        $configurationHelper = new ConfigurationHelper($this->getSqliteConnection());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Given config file type is not supported');

        $configurationHelper->getMigrationConfig($this->input, $this->getOutputWriter());
    }

    public function testConfigurationHelperWithConfigurationFromSetter() : void
    {
        $this->input->method('getOption')
            ->with('configuration')
            ->will($this->returnValue(null));

        $configurationHelper = new ConfigurationHelper($this->connection, $this->configuration);

        $migrationConfig = $configurationHelper->getMigrationConfig($this->input, $this->getOutputWriter());

        self::assertInstanceOf(Configuration::class, $migrationConfig);
        self::assertAttributeSame($this->getOutputWriter(), 'outputWriter', $migrationConfig);
        self::assertStringMatchesFormat('Loading configuration from the integration code of your framework (setter).', trim($this->getOutputStreamContent($this->output)));
    }

    public function testConfigurationHelperWithConfigurationFromSetterAndOverrideFromCommandLine() : void
    {
        $this->input->method('getOption')
            ->with('configuration')
            ->will($this->returnValue(__DIR__ . '/../Command/_files/config.yml'));

        $configurationHelper = new ConfigurationHelper($this->connection, $this->configuration);

        $migrationConfig = $configurationHelper->getMigrationConfig($this->input, $this->getOutputWriter());

        self::assertInstanceOf(Configuration::class, $migrationConfig);
        self::assertAttributeSame($this->getOutputWriter(), 'outputWriter', $migrationConfig);
        self::assertStringMatchesFormat('Loading configuration from command option: %a', $this->getOutputStreamContent($this->output));
    }

    public function testConfigurationHelperWithoutConfigurationFromSetterAndWithoutOverrideFromCommandLineAndWithoutConfigInPath() : void
    {
        $this->input->method('getOption')
            ->with('configuration')
            ->will($this->returnValue(null));

        $configurationHelper = new ConfigurationHelper($this->connection, null);

        $migrationConfig = $configurationHelper->getMigrationConfig($this->input, $this->getOutputWriter());

        self::assertInstanceOf(Configuration::class, $migrationConfig);
        self::assertAttributeSame($this->getOutputWriter(), 'outputWriter', $migrationConfig);
        self::assertStringMatchesFormat('', $this->getOutputStreamContent($this->output));
    }
}
