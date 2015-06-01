<?php

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;

class AbstractCommandTest extends MigrationTestCase
{
    private $originalCwd;

    /**
     * Invoke invisible migration configuration getter
     *
     * @param mixed $input
     * @param mixed $configuration
     *
     * @return \Doctrine\DBAL\Migrations\Configuration\Configuration
     */
    public function invokeMigrationConfigurationGetter($input, $configuration = null)
    {
        $class = new \ReflectionClass('Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand');
        $method = $class->getMethod('getMigrationConfiguration');
        $method->setAccessible(true);

        /** @var \Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand $command */
        $command = $this->getMockForAbstractClass(
            'Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand',
            array('command')
        );

        $command->setHelperSet(new HelperSet(array(
            'connection' => new ConnectionHelper($this->getSqliteConnection())
        )));
        if (null !== $configuration) {
            $command->setMigrationConfiguration($configuration);
        }

        $output = $this->getMockBuilder('Symfony\Component\Console\Output\Output')
            ->setMethods(array('doWrite', 'writeln'))
            ->getMock();

        $output->expects($this->any())
            ->method('doWrite');

        return $method->invokeArgs($command, array($input, $output));
    }


    /**
     * Test if the returned migration configuration is the injected one
     */
    public function testInjectedMigrationConfigurationIsBeingReturned()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\ArrayInput')
            ->setConstructorArgs(array(array()))
            ->setMethods(array('getOption'))
            ->getMock();

        $input->expects($this->any())
            ->method('getOption')
            ->with($this->logicalOr($this->equalTo('db-configuration'), $this->equalTo('configuration')))
            ->will($this->returnValue(null));

        $configuration = $this
            ->getMockBuilder('Doctrine\DBAL\Migrations\Configuration\Configuration')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertEquals($configuration, $this->invokeMigrationConfigurationGetter($input, $configuration));
    }

    /**
     * Test if the migration configuration returns the connection from the helper set
     */
    public function testMigrationConfigurationReturnsConnectionFromHelperSet()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\ArrayInput')
            ->setConstructorArgs(array(array()))
            ->setMethods(array('getOption'))
            ->getMock();

        $input->expects($this->any())
            ->method('getOption')
            ->with($this->logicalOr($this->equalTo('db-configuration'), $this->equalTo('configuration')))
            ->will($this->returnValue(null));

        $actualConfiguration = $this->invokeMigrationConfigurationGetter($input);

        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Configuration\Configuration', $actualConfiguration);
        $this->assertEquals($this->getSqliteConnection(), $actualConfiguration->getConnection());
    }

    /**
     * Test if the migration configuration returns the connection from the input option
     */
    public function testMigrationConfigurationReturnsConnectionFromInputOption()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\ArrayInput')
            ->setConstructorArgs(array(array()))
            ->setMethods(array('getOption'))
            ->getMock();

        $input->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap(array(
                array('db-configuration', __DIR__ . '/_files/db-config.php')
            )));

        $actualConfiguration = $this->invokeMigrationConfigurationGetter($input);

        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Configuration\Configuration', $actualConfiguration);
        $this->assertEquals($this->getSqliteConnection(), $actualConfiguration->getConnection());
    }

    /**
     * Test if the migration configuration returns values from the configuration file
     */
    public function testMigrationConfigurationReturnsConfigurationFileOption()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\ArrayInput')
            ->setConstructorArgs(array(array()))
            ->setMethods(array('getOption'))
            ->getMock();

        $input->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap(array(
                array('configuration', __DIR__ . '/_files/config.yml')
            )));

        $actualConfiguration = $this->invokeMigrationConfigurationGetter($input);

        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Configuration\YamlConfiguration', $actualConfiguration);
        $this->assertEquals('name', $actualConfiguration->getName());
        $this->assertEquals('migrations_table_name', $actualConfiguration->getMigrationsTableName());
        $this->assertEquals('migrations_namespace', $actualConfiguration->getMigrationsNamespace());
    }

    public function testMigrationsConfigurationFromCommandLineOverridesInjectedConfiguration()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\ArrayInput')
            ->setConstructorArgs(array(array()))
            ->setMethods(array('getOption'))
            ->getMock();

        $input->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap(array(
                array('configuration', __DIR__ . '/_files/config.yml')
            )));

        $configuration = $this
            ->getMockBuilder('Doctrine\DBAL\Migrations\Configuration\Configuration')
            ->disableOriginalConstructor()
            ->getMock();

        $actualConfiguration = $this->invokeMigrationConfigurationGetter($input, $configuration);

        $this->assertInstanceOf('Doctrine\DBAL\Migrations\Configuration\YamlConfiguration', $actualConfiguration);
        $this->assertEquals('name', $actualConfiguration->getName());
        $this->assertEquals('migrations_table_name', $actualConfiguration->getMigrationsTableName());
        $this->assertEquals('migrations_namespace', $actualConfiguration->getMigrationsNamespace());
    }

    /**
     * @see https://github.com/doctrine/migrations/issues/228
     * @group regression
     */
    public function testInjectedConfigurationIsPreferedOverConfigFileIsCurrentWorkingDirectory()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\ArrayInput')
            ->setConstructorArgs(array(array()))
            ->setMethods(array('getOption'))
            ->getMock();

        $input->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap(array(
                array('configuration', null)
            )));

        $configuration = $this
            ->getMockBuilder('Doctrine\DBAL\Migrations\Configuration\Configuration')
            ->disableOriginalConstructor()
            ->getMock();

        chdir(__DIR__.'/_files');
        $actualConfiguration = $this->invokeMigrationConfigurationGetter($input, $configuration);

        $this->assertSame($configuration, $actualConfiguration);
    }

    public function invokeAbstractCommandConfirmation($input, $helper, $response="y", $question="There is no question?")
    {
        $class = new \ReflectionClass('Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand');
        $method = $class->getMethod('askConfirmation');
        $method->setAccessible(true);

        /** @var \Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand $command */
        $command = $this->getMockForAbstractClass(
            'Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand',
            array('command')
        );

        $helper->setInputStream($this->getInputStream($response . "\n"));
        if ($helper instanceof QuestionHelper) {
            $helperSet = new HelperSet(array(
                'question' => $helper
            ));
        } else {
            $helperSet = new HelperSet(array(
                'dialog' => $helper
            ));
        }

        $command->setHelperSet($helperSet);

        $output = $this->getMockBuilder('Symfony\Component\Console\Output\Output')
            ->setMethods(array('doWrite', 'writeln'))
            ->getMock();

        $output->expects($this->any())
            ->method('doWrite');

        return $method->invokeArgs($command, array($question, $input, $output));
    }

    public function testAskConfirmation()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\ArrayInput')
            ->setConstructorArgs(array(array()))
            ->setMethods(array('getOption'))
            ->getMock();

        try {
            $helper = new DialogHelper();

            $this->assertEquals(true, $this->invokeAbstractCommandConfirmation($input, $helper));
            $this->assertEquals(false, $this->invokeAbstractCommandConfirmation($input, $helper, "n"));
        } catch (\PHPUnit_Framework_Error_Deprecated $error) {

        }

        if (class_exists("Symfony\\Component\\Console\\Helper\\QuestionHelper")) {
            $helper = new QuestionHelper();
            $this->assertEquals(true, $this->invokeAbstractCommandConfirmation($input, $helper));
            $this->assertEquals(false, $this->invokeAbstractCommandConfirmation($input, $helper, "n"));
        }
    }

    private function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }

    protected function setUp()
    {
        $this->originalCwd = getcwd();
    }

    protected function tearDown()
    {
        if (getcwd() !== $this->originalCwd) {
            chdir($this->originalCwd);
        }
    }
}
