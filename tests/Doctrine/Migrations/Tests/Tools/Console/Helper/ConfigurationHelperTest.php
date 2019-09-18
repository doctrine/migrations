<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\ArrayLoader;
use Doctrine\Migrations\Configuration\JsonConfiguration;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Helper\ConfigurationHelper;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use function copy;
use function trim;
use function unlink;

class ConfigurationHelperTest extends MigrationTestCase
{
    /** @var Connection */
    private $connection;

    /** @var StreamOutput */
    protected $output;

    /** @var InputInterface|MockObject */
    private $input;

    protected function setUp() : void
    {
        $this->connection = $this->getSqliteConnection();

        $this->output = $this->getOutputStream();

        $this->input = $this->getMockBuilder(ArrayInput::class)
            ->setConstructorArgs([[]])
            ->setMethods(['getOption'])
            ->getMock();
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

        self::assertInstanceOf(ArrayLoader::class, $migrationConfig);
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

        self::assertStringMatchesFormat('', $this->getOutputStreamContent($this->output));
    }
}
