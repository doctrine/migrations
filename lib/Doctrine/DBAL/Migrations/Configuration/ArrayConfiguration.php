<?php

namespace Doctrine\DBAL\Migrations\Configuration;

/**
 * Load migration configuration information from a PHP configuration file.
 */
class ArrayConfiguration extends AbstractFileConfiguration
{
    /**
     * @inheritdoc
     */
    protected function doLoad($file)
    {
        $config = require $file;

        if (isset($config['migrations_directory'])) {
            $config['migrations_directory'] = $this->getDirectoryRelativeToFile($file, $config['migrations_directory']);
        }

        $this->setConfiguration($config);
    }
}
