<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration;

use Doctrine\Migrations\MigrationException;
use function file_get_contents;
use function json_decode;

class JsonConfiguration extends AbstractFileConfiguration
{
    /** @inheritdoc */
    protected function doLoad(string $file) : void
    {
        $config = json_decode(file_get_contents($file), true);

        if ($config === false) {
            throw MigrationException::configurationNotValid('Configuration is not valid JSON.');
        }

        if (isset($config['migrations_directory'])) {
            $config['migrations_directory'] = $this->getDirectoryRelativeToFile(
                $file,
                $config['migrations_directory']
            );
        }

        $this->setConfiguration($config);
    }
}
