<?php
namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\ConfigurationHelper;
use Doctrine\ORM\Configuration;
use Symfony\Component\Console\Output\BufferedOutput;

class ConfigurationHelperTest extends MigrationTestCase {

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

    public function setUp()
    {
        $this->configuration = $this->getSqliteConfiguration();

//        $configuration = $this
//            ->getMockBuilder('Doctrine\DBAL\Migrations\Configuration\Configuration')
//            ->disableOriginalConstructor()
//            ->getMock();

        $this->connection = $this->getSqliteConnection();
    }

    public function testConfigurationHelper()
    {
        $configurationHelper = new ConfigurationHelper($this->connection, $this->configuration);

        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Tools\Console\Helper\ConfigurationHelper', $configurationHelper);
    }

    public function testConfigurationHelperWithConfigurationOption()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\ArrayInput')
            ->setConstructorArgs(array(array()))
            ->setMethods(array('getOption'))
            ->getMock();

        $input->expects($this->any())
            ->method('getOption')
            ->with($this->logicalOr($this->equalTo('db-configuration'), $this->equalTo('configuration')))
            ->will($this->returnValue(null));

//        $input->expects($this->any())
//            ->method('getOption')
//            ->with($this->logicalOr($this->equalTo('db-configuration'), $this->equalTo('configuration')))
//            ->will($this->returnValueMap(array(
//                array('db-configuration', __DIR__ . '../Command/_files/db-config.php')
//            )));

        $configurationHelper = new ConfigurationHelper($this->connection, $this->configuration);

        $migrationConfig = $configurationHelper->getMigrationConfig($input, $this->getOutputWriter());

        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Configuration\Configuration', $migrationConfig);
    }

    private function getOutputWriter()
    {
        if (!$this->outputWriter) {
            $output = new BufferedOutput();
            $this->outputWriter = new OutputWriter(function ($message) use ($output) {
                return $output->writeln($message);
            });
        }
        return $this->getOutputWriter();
    }
}