<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\YamlConfiguration;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Command\AbstractCommand;
use Doctrine\Migrations\Tools\Console\Helper\ConfigurationHelper;
use InvalidArgumentException;
use ReflectionClass;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use function assert;
use function chdir;
use function getcwd;

class AbstractCommandTest extends MigrationTestCase
{
    /** @var string */
    private $originalCwd;

    /**
     * Invoke invisible migration configuration getter
     */
    public function invokeMigrationConfigurationGetter(
        InputInterface $input,
        ?Configuration $configuration = null,
        bool $noConnection = false,
        ?HelperSet $helperSet = null
    ) : Configuration {
        $class  = new ReflectionClass(AbstractCommand::class);
        $method = $class->getMethod('getConfigurationuration');
        $method->setAccessible(true);

        /** @var AbstractCommand $command */
        $command = $this->getMockForAbstractClass(
            AbstractCommand::class,
            ['command']
        );

        if ($helperSet instanceof HelperSet) {
            $command->setHelperSet($helperSet);
        } else {
            $command->setHelperSet(new HelperSet());
        }

        if (! $noConnection) {
            $command->getHelperSet()->set(
                new ConnectionHelper($this->getSqliteConnection()),
                'connection'
            );
        }

        if ($configuration !== null) {
            $command->setMigrationConfiguration($configuration);
        }

        $output = $this->getMockBuilder(Output::class)
            ->setMethods(['doWrite', 'writeln'])
            ->getMock();

        return $method->invokeArgs($command, [$input, $output]);
    }

    public function testSetConnection() : void
    {
        $command    = new TestAbstractCommand();
        $connection = $this->createMock(Connection::class);

        $command->setConnection($connection);

        self::assertSame($connection, $command->getConnection());
    }

    public function testgetConfigurationurationDefaultsToConnection() : void
    {
        $configuration = $this->createMock(Configuration::class);
        $input         = $this->createMock(InputInterface::class);
        $output        = $this->createMock(OutputInterface::class);

        $command = new TestAbstractCommand();
        $command->setMigrationConfiguration($configuration);

        self::assertSame($configuration, $command->getConfiguration($input, $output));
    }

    /**
     * Test if the returned migration configuration is the injected one
     */
    public function testInjectedMigrationConfigurationIsBeingReturned() : void
    {
        $input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->setMethods(['getOption'])
            ->getMock();

        $input->method('getOption')
            ->with(self::logicalOr(self::equalTo('db-configuration'), self::equalTo('configuration')))
            ->will(self::returnValue(null));

        $configuration = $this->createMock(Configuration::class);

        self::assertSame($configuration, $this->invokeMigrationConfigurationGetter($input, $configuration));
    }

    /**
     * Test if the migration configuration returns the connection from the helper set
     */
    public function testMigrationConfigurationReturnsConnectionFromHelperSet() : void
    {
        $input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->setMethods(['getOption'])
            ->getMock();

        $input->method('getOption')
            ->with(self::logicalOr(self::equalTo('db-configuration'), self::equalTo('configuration')))
            ->will(self::returnValue(null));

        $actualConfiguration = $this->invokeMigrationConfigurationGetter($input);

        self::assertSame('pdo_sqlite', $actualConfiguration->getConnection()->getDriver()->getName());
    }

    /**
     * Test if the migration configuration returns the connection from the input option
     */
    public function testMigrationConfigurationReturnsConnectionFromInputOption() : void
    {
        $input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->setMethods(['getOption'])
            ->getMock();

        $input->method('getOption')
            ->will(self::returnValueMap([
                ['db-configuration', __DIR__ . '/_files/db-config.php'],
            ]));

        $actualConfiguration = $this->invokeMigrationConfigurationGetter($input);

        self::assertSame('pdo_sqlite', $actualConfiguration->getConnection()->getDriver()->getName());
    }

    /**
     * Test if the migration configuration returns values from the configuration file
     */
    public function testMigrationConfigurationReturnsConfigurationFileOption() : void
    {
        $input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->setMethods(['getOption'])
            ->getMock();

        $input->method('getOption')
            ->will(self::returnValueMap([
                ['configuration', __DIR__ . '/_files/config.yml'],
            ]));

        $actualConfiguration = $this->invokeMigrationConfigurationGetter($input);

        self::assertInstanceOf(YamlConfiguration::class, $actualConfiguration);
        self::assertSame('name', $actualConfiguration->getName());
        self::assertSame('migrations_table_name', $actualConfiguration->getMigrationsTableName());
        self::assertSame('migrations_namespace', $actualConfiguration->getMigrationsNamespace());
    }

    /**
     * Test if the migration configuration use the connection in a configuration passed to it.
     */
    public function testMigrationConfigurationReturnsConnectionFromConfigurationIfNothingElseIsProvided() : void
    {
        $input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->getMock();

        $connection          = $this->getSqliteConnection();
        $configuration       = new Configuration($connection);
        $actualConfiguration = $this->invokeMigrationConfigurationGetter($input, $configuration, true);

        self::assertSame($connection, $actualConfiguration->getConnection());
        self::assertSame('doctrine_migration_versions', $actualConfiguration->getMigrationsTableName());
        self::assertNull($actualConfiguration->getMigrationsNamespace());
    }

    /**
     * Test if throw an error if no connection is passed.
     */
    public function testMigrationConfigurationReturnsErrorWhenNoConnectionIsProvided() : void
    {
        $input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->getMock();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You have to specify a --db-configuration file or pass a Database Connection as a dependency to the Migrations.');

        $this->invokeMigrationConfigurationGetter($input, null, true);
    }

    public function testMigrationsConfigurationFromCommandLineOverridesInjectedConfiguration() : void
    {
        $input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->setMethods(['getOption'])
            ->getMock();

        $input->method('getOption')
            ->will(self::returnValueMap([
                ['configuration', __DIR__ . '/_files/config.yml'],
            ]));

        $configuration = $this
            ->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $actualConfiguration = $this->invokeMigrationConfigurationGetter($input, $configuration);

        self::assertInstanceOf(YamlConfiguration::class, $actualConfiguration);
        self::assertSame('name', $actualConfiguration->getName());
        self::assertSame('migrations_table_name', $actualConfiguration->getMigrationsTableName());
        self::assertSame('migrations_namespace', $actualConfiguration->getMigrationsNamespace());
    }

    /**
     * @see https://github.com/doctrine/migrations/issues/228
     *
     * @group regression
     */
    public function testInjectedConfigurationIsPreferedOverConfigFileIsCurrentWorkingDirectory() : void
    {
        $input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->setMethods(['getOption'])
            ->getMock();

        $input->method('getOption')
            ->will(self::returnValueMap([
                ['configuration', null],
            ]));

        $configuration = $this->createMock(Configuration::class);

        chdir(__DIR__ . '/_files');
        $actualConfiguration = $this->invokeMigrationConfigurationGetter($input, $configuration);

        self::assertSame($configuration, $actualConfiguration);
    }

    /**
     * Test if the migration configuration can be set via ConfigurationHelper in HelperSet
     */
    public function testMigrationsConfigurationFromConfighelperInHelperset() : void
    {
        $input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->getMock();

        $configuration = $this
            ->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $helperSet    = new HelperSet();
        $configHelper = new ConfigurationHelper($configuration);
        $helperSet->set($configHelper, 'configuration');

        $actualConfiguration = $this->invokeMigrationConfigurationGetter($input, null, false, $helperSet);

        self::assertSame($configuration, $actualConfiguration);
    }

    private function invokeAbstractCommandConfirmation(
        ArrayInput $input,
        QuestionHelper $helper,
        string $response = 'y',
        string $question = 'There is no question?'
    ) : bool {
        $class  = new ReflectionClass(AbstractCommand::class);
        $method = $class->getMethod('askConfirmation');
        $method->setAccessible(true);

        /** @var AbstractCommand $command */
        $command = $this->getMockForAbstractClass(
            AbstractCommand::class,
            ['command']
        );

        $input->setStream($this->getInputStream($response . "\n"));

        $helperSet = new HelperSet(['question' => $helper]);

        $command->setHelperSet($helperSet);

        $output = $this->getMockBuilder(Output::class)
            ->setMethods(['doWrite', 'writeln'])
            ->getMock();

        return $method->invokeArgs($command, [$question, $input, $output]);
    }

    public function testAskConfirmation() : void
    {
        $input  = new ArrayInput([]);
        $helper = new QuestionHelper();

        self::assertTrue($this->invokeAbstractCommandConfirmation($input, $helper));
        self::assertFalse($this->invokeAbstractCommandConfirmation($input, $helper, 'n'));
    }

    protected function setUp() : void
    {
        $cwd = getcwd();

        assert($cwd !== false);

        $this->originalCwd = $cwd;
    }

    protected function tearDown() : void
    {
        if (getcwd() === $this->originalCwd) {
            return;
        }

        chdir($this->originalCwd);
    }
}

class TestAbstractCommand extends AbstractCommand
{
    public function getConnection() : Connection
    {
        return $this->connection;
    }

    public function getConfiguration(InputInterface $input, OutputInterface $output) : Configuration
    {
        return $this->getConfigurationuration($input, $output);
    }
}
