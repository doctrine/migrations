<?php

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Configuration\YamlConfiguration;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\ConfigurationHelper;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\Output;

class AbstractCommandTest extends MigrationTestCase
{
    private $originalCwd;

    /**
     * Invoke invisible migration configuration getter
     *
     * @param mixed $input
     * @param mixed $configuration
     * @param bool $noConnection
     * @param mixed $helperSet
     *
     * @return Configuration
     */
    public function invokeMigrationConfigurationGetter($input, $configuration = null, $noConnection = false, $helperSet = null)
    {
        $class  = new \ReflectionClass(AbstractCommand::class);
        $method = $class->getMethod('getMigrationConfiguration');
        $method->setAccessible(true);

        /** @var AbstractCommand $command */
        $command = $this->getMockForAbstractClass(
            AbstractCommand::class,
            ['command']
        );

        if ($helperSet != null && $helperSet instanceof HelperSet) {
            $command->setHelperSet($helperSet);
        } else {
            $command->setHelperSet(new HelperSet());
        }

        if ( ! $noConnection) {
            $command->getHelperSet()->set(
                new ConnectionHelper($this->getSqliteConnection()),
                'connection'
            );
        }

        if (null !== $configuration) {
            $command->setMigrationConfiguration($configuration);
        }

        $output = $this->getMockBuilder(Output::class)
            ->setMethods(['doWrite', 'writeln'])
            ->getMock();

        return $method->invokeArgs($command, [$input, $output]);
    }


    /**
     * Test if the returned migration configuration is the injected one
     */
    public function testInjectedMigrationConfigurationIsBeingReturned()
    {
        $input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->setMethods(['getOption'])
            ->getMock();

        $input->method('getOption')
            ->with($this->logicalOr($this->equalTo('db-configuration'), $this->equalTo('configuration')))
            ->will($this->returnValue(null));

        $configuration = $this->createMock(Configuration::class);

        self::assertEquals($configuration, $this->invokeMigrationConfigurationGetter($input, $configuration));
    }

    /**
     * Test if the migration configuration returns the connection from the helper set
     */
    public function testMigrationConfigurationReturnsConnectionFromHelperSet()
    {
        $input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->setMethods(['getOption'])
            ->getMock();

        $input->method('getOption')
            ->with($this->logicalOr($this->equalTo('db-configuration'), $this->equalTo('configuration')))
            ->will($this->returnValue(null));

        $actualConfiguration = $this->invokeMigrationConfigurationGetter($input);

        self::assertInstanceOf(Configuration::class, $actualConfiguration);
        self::assertEquals($this->getSqliteConnection(), $actualConfiguration->getConnection());
    }

    /**
     * Test if the migration configuration returns the connection from the input option
     */
    public function testMigrationConfigurationReturnsConnectionFromInputOption()
    {
        $input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->setMethods(['getOption'])
            ->getMock();

        $input->method('getOption')
            ->will($this->returnValueMap([
                ['db-configuration', __DIR__ . '/_files/db-config.php']
            ]));

        $actualConfiguration = $this->invokeMigrationConfigurationGetter($input);

        self::assertInstanceOf(Configuration::class, $actualConfiguration);
        self::assertEquals($this->getSqliteConnection(), $actualConfiguration->getConnection());
    }

    /**
     * Test if the migration configuration returns values from the configuration file
     */
    public function testMigrationConfigurationReturnsConfigurationFileOption()
    {
        $input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->setMethods(['getOption'])
            ->getMock();

        $input->method('getOption')
            ->will($this->returnValueMap([
                ['configuration', __DIR__ . '/_files/config.yml']
            ]));

        $actualConfiguration = $this->invokeMigrationConfigurationGetter($input);

        self::assertInstanceOf(YamlConfiguration::class, $actualConfiguration);
        self::assertEquals('name', $actualConfiguration->getName());
        self::assertEquals('migrations_table_name', $actualConfiguration->getMigrationsTableName());
        self::assertEquals('migrations_namespace', $actualConfiguration->getMigrationsNamespace());
    }

    /**
     * Test if the migration configuration use the connection in a configuration passed to it.
     */
    public function testMigrationConfigurationReturnsConnectionFromConfigurationIfNothingElseIsProvided()
    {
        $input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->getMock();

        $configuration       = new Configuration($this->getSqliteConnection());
        $actualConfiguration = $this->invokeMigrationConfigurationGetter($input, $configuration, true);

        self::assertInstanceOf(Configuration::class, $actualConfiguration);
        self::assertEquals($this->getSqliteConnection(), $actualConfiguration->getConnection());
        self::assertEquals('doctrine_migration_versions', $actualConfiguration->getMigrationsTableName());
        self::assertNull($actualConfiguration->getMigrationsNamespace());
    }

    /**
     * Test if throw an error if no connection is passed.
     */
    public function testMigrationConfigurationReturnsErrorWhenNoConnectionIsProvided()
    {
        $input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->getMock();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You have to specify a --db-configuration file or pass a Database Connection as a dependency to the Migrations.');

        $this->invokeMigrationConfigurationGetter($input, null, true);
    }

    public function testMigrationsConfigurationFromCommandLineOverridesInjectedConfiguration()
    {
        $input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->setMethods(['getOption'])
            ->getMock();

        $input->method('getOption')
            ->will($this->returnValueMap([
                ['configuration', __DIR__ . '/_files/config.yml']
            ]));

        $configuration = $this
            ->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $actualConfiguration = $this->invokeMigrationConfigurationGetter($input, $configuration);

        self::assertInstanceOf(YamlConfiguration::class, $actualConfiguration);
        self::assertEquals('name', $actualConfiguration->getName());
        self::assertEquals('migrations_table_name', $actualConfiguration->getMigrationsTableName());
        self::assertEquals('migrations_namespace', $actualConfiguration->getMigrationsNamespace());
    }

    /**
     * @see https://github.com/doctrine/migrations/issues/228
     * @group regression
     */
    public function testInjectedConfigurationIsPreferedOverConfigFileIsCurrentWorkingDirectory()
    {
        $input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->setMethods(['getOption'])
            ->getMock();

        $input->method('getOption')
            ->will($this->returnValueMap([
                ['configuration', null]
            ]));

        $configuration = $this->createMock(Configuration::class);

        chdir(__DIR__ . '/_files');
        $actualConfiguration = $this->invokeMigrationConfigurationGetter($input, $configuration);

        self::assertSame($configuration, $actualConfiguration);
    }

    /**
     * Test if the migration configuration can be set via ConfigurationHelper in HelperSet
     */
    public function testMigrationsConfigurationFromConfighelperInHelperset()
    {
        $input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->getMock();

        $configuration = $this
            ->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $helperSet    = new HelperSet();
        $configHelper = new ConfigurationHelper($this->getSqliteConnection(), $configuration);
        $helperSet->set($configHelper, 'configuration');

        $actualConfiguration = $this->invokeMigrationConfigurationGetter($input, null, false, $helperSet);

        self::assertSame($configuration, $actualConfiguration);
    }

    private function invokeAbstractCommandConfirmation($input, $helper, $response = "y", $question = "There is no question?")
    {
        $class  = new \ReflectionClass(AbstractCommand::class);
        $method = $class->getMethod('askConfirmation');
        $method->setAccessible(true);

        /** @var AbstractCommand $command */
        $command = $this->getMockForAbstractClass(
            AbstractCommand::class,
            ['command']
        );

        $input->setStream($this->getInputStream($response . "\n"));
        if ($helper instanceof QuestionHelper) {
            $helperSet = new HelperSet([
                'question' => $helper
            ]);
        } else {
            $helperSet = new HelperSet([
                'dialog' => $helper
            ]);
        }

        $command->setHelperSet($helperSet);

        $output = $this->getMockBuilder(Output::class)
            ->setMethods(['doWrite', 'writeln'])
            ->getMock();

        return $method->invokeArgs($command, [$question, $input, $output]);
    }

    public function testAskConfirmation()
    {
        $input  = new ArrayInput([]);
        $helper = new QuestionHelper();

        self::assertTrue($this->invokeAbstractCommandConfirmation($input, $helper));
        self::assertFalse($this->invokeAbstractCommandConfirmation($input, $helper, "n"));
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
