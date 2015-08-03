<?php
namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\ConfigurationHelper;
use Doctrine\ORM\Configuration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use InvalidArgumentException;

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
     * @var InputInterface
     */
    private $input;

    public function setUp()
    {
        $this->configuration = $this->getSqliteConfiguration();

        $this->connection = $this->getSqliteConnection();

        $this->input = $this->getMockBuilder('Symfony\Component\Console\Input\ArrayInput')
            ->setConstructorArgs([[]])
            ->setMethods(['getOption'])
            ->getMock();
    }

    public function testConfigurationHelper()
    {
        $configurationHelper = new ConfigurationHelper($this->connection, $this->configuration);

        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Tools\Console\Helper\ConfigurationHelper', $configurationHelper);
    }

    //used in other tests to see if xml or yaml or yml config files are loaded.
    protected function getConfigurationHelperLoadsASpecificFormat($baseFile, $configFile) {
        try {
            $file = 'tests/Doctrine/DBAL/Migrations/Tests/Tools/Console/Helper/files/' . $baseFile;
            copy($file,$configFile);

            $this->input->expects($this->any())
                ->method('getOption')
                ->with('configuration')
                ->will($this->returnValue(null));

            $configurationHelper = new ConfigurationHelper($this->getSqliteConnection());
            $configfileLoaded = $configurationHelper->getMigrationConfig($this->input, $this->getOutputWriter());

            unlink($configFile);

            return trim($this->getOutputStreamContent($this->output));
        }
        catch(\Exception $e) {
            unlink($configFile);//i want to be really sure to cleanup this file
        }
        return false;
    }

    public function testConfigurationHelperLoadsXmlFormat() {
        $this->assertStringMatchesFormat(
            'Loading configuration from file: migrations.xml',
            $this->getConfigurationHelperLoadsASpecificFormat('config.xml', 'migrations.xml')
        );
    }

    public function testConfigurationHelperLoadsYamlFormat() {
        $this->assertStringMatchesFormat(
            'Loading configuration from file: migrations.yaml',
            $this->getConfigurationHelperLoadsASpecificFormat('config.yml', 'migrations.yaml')
        );
    }

    public function testConfigurationHelperLoadsYmlFormat() {
        $this->assertStringMatchesFormat(
            'Loading configuration from file: migrations.yml',
            $this->getConfigurationHelperLoadsASpecificFormat('config.yml', 'migrations.yml')
        );
    }

    public function testConfigurationHelperLoadsJsonFormat() {
        $this->assertStringMatchesFormat(
            'Loading configuration from file: migrations.json',
            $this->getConfigurationHelperLoadsASpecificFormat('config.json', 'migrations.json')
        );
    }

    public function testConfigurationHelperLoadsPhpArrayFormatFromCommandLine() {
        $this->input->expects($this->any())
            ->method('getOption')
            ->with('configuration')
            ->will($this->returnValue( __DIR__ . '/files/config.php'));

        $configurationHelper = new ConfigurationHelper($this->getSqliteConnection());
        $migrationConfig = $configurationHelper->getMigrationConfig($this->input, $this->getOutputWriter());

        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Configuration\ArrayConfiguration', $migrationConfig);
        $this->assertSame('DoctrineMigrationsTest', $migrationConfig->getMigrationsNamespace());
    }

    public function testConfigurationHelperLoadsJsonFormatFromCommandLine() {
        $this->input->expects($this->any())
            ->method('getOption')
            ->with('configuration')
            ->will($this->returnValue( __DIR__ . '/files/config.json'));

        $configurationHelper = new ConfigurationHelper($this->getSqliteConnection());
        $migrationConfig = $configurationHelper->getMigrationConfig($this->input, $this->getOutputWriter());

        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Configuration\JsonConfiguration', $migrationConfig);
        $this->assertSame('DoctrineMigrationsTest', $migrationConfig->getMigrationsNamespace());
    }

    /**
     * Test that unsupported file type throws exception
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Given config file type is not supported
     */
    public function testConfigurationHelperFailsToLoadOtherFormat() {

        $this->input->expects($this->any())
            ->method('getOption')
            ->with('configuration')
            ->will($this->returnValue('testconfig.wrong'));

        $configurationHelper = new ConfigurationHelper($this->getSqliteConnection());
        $configfileLoaded = $configurationHelper->getMigrationConfig($this->input, $this->getOutputWriter());
    }

    public function testConfigurationHelperWithConfigurationFromSetter()
    {
        $this->input->expects($this->any())
            ->method('getOption')
            ->with('configuration')
            ->will($this->returnValue(null));

        $configurationHelper = new ConfigurationHelper($this->connection, $this->configuration);

        $migrationConfig = $configurationHelper->getMigrationConfig($this->input, $this->getOutputWriter());

        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Configuration\Configuration', $migrationConfig);

        $this->assertStringMatchesFormat("Loading configuration from the integration code of your framework (setter).", trim($this->getOutputStreamContent($this->output)));
    }

    public function testConfigurationHelperWithConfigurationFromSetterAndOverrideFromCommandLine()
    {
        $this->input->expects($this->any())
            ->method('getOption')
            ->with('configuration')
            ->will($this->returnValue(__DIR__ . '/../Command/_files/config.yml'));

        $configurationHelper = new ConfigurationHelper($this->connection, $this->configuration);

        $migrationConfig = $configurationHelper->getMigrationConfig($this->input, $this->getOutputWriter());

        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Configuration\Configuration', $migrationConfig);
        $this->assertStringMatchesFormat("Loading configuration from command option: %a", $this->getOutputStreamContent($this->output));
    }

    public function testConfigurationHelperWithoutConfigurationFromSetterAndWithoutOverrideFromCommandLineAndWithoutConfigInPath()
    {
        $this->input->expects($this->any())
            ->method('getOption')
            ->with('configuration')
            ->will($this->returnValue(null));

        $configurationHelper = new ConfigurationHelper($this->connection, null);

        $migrationConfig = $configurationHelper->getMigrationConfig($this->input, $this->getOutputWriter());

        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Configuration\Configuration', $migrationConfig);
        $this->assertStringMatchesFormat("", $this->getOutputStreamContent($this->output));
    }

}
