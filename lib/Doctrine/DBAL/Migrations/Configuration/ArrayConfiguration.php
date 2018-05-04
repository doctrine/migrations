<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Configuration;

class ArrayConfiguration extends AbstractFileConfiguration
{
    /** @inheritdoc */
    protected function doLoad(string $file) : void
    {
        $config = require $file;

        if (isset($config['migrations_directory'])) {
            $config['migrations_directory'] = $this->getDirectoryRelativeToFile(
                $file,
                $config['migrations_directory']
            );
        }

        $this->setConfiguration($config);
    }
}
