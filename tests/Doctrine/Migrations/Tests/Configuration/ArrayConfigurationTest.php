<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration;

use Doctrine\Migrations\Configuration\AbstractFileConfiguration;
use Doctrine\Migrations\Configuration\ArrayConfiguration;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\OutputWriter;
use InvalidArgumentException;

use const DIRECTORY_SEPARATOR;

class ArrayConfigurationTest extends AbstractConfigurationTest
{
    public function loadConfiguration(
        string $configFileSuffix = '',
        ?OutputWriter $outputWriter = null,
        ?MigrationFinder $migrationFinder = null
    ): AbstractFileConfiguration {
        $configFile = 'config.php';

        if ($configFileSuffix !== '') {
            $configFile = 'config_' . $configFileSuffix . '.php';
        }

        $config = new ArrayConfiguration($this->getSqliteConnection(), $outputWriter, $migrationFinder);
        $config->load(__DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . $configFile);

        return $config;
    }

    /**
     * Test that config file not exists exception
     */
    public function testThrowExceptionIfFileNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Given config file does not exist');

        $config = new ArrayConfiguration($this->getSqliteConnection());
        $config->load(__DIR__ . '/_files/none.php');
    }
}
