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
    private $outputWriter;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var InputInterface
     */
    private $input;

    public function setUp()
    {
        $this->configuration = $this->getSqliteConfiguration();

        $this->connection = $this->getSqliteConnection();

        $this->input = $this->getMockBuilder('Symfony\Component\Console\Input\ArrayInput')
            ->setConstructorArgs(array(array()))
            ->setMethods(array('getOption'))
            ->getMock();
    }

    public function testConfigurationHelper()
    {
        $configurationHelper = new ConfigurationHelper($this->connection, $this->configuration);

        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Tools\Console\Helper\ConfigurationHelper', $configurationHelper);
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
        $this->assertStringMatchesFormat("Loading configuration from the integration code of your framework (setter).", $this->getOutputStreamContent($this->output));
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

    private function getOutputWriter()
    {
        if (!$this->outputWriter) {
            $this->output = $this->getOutputStream();
            $output = $this->output;
            $this->outputWriter = new OutputWriter(function ($message) use ($output) {
                return $output->writeln($message);
            });
        }
        return $this->outputWriter;
    }
}