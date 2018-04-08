<?php

namespace Doctrine\DBAL\Migrations\Configuration;

/**
 * Load migration configuration information from a PHP configuration file.
 */
class JsonConfiguration extends AbstractFileConfiguration
{
    /**
     * @inheritdoc
     */
    protected function doLoad($file)
    {
        $config = json_decode(file_get_contents($file), true);

        if (isset($config['migrations_directory'])) {
            $config['migrations_directory'] = $this->getDirectoryRelativeToFile($file, $config['migrations_directory']);
        }

        $this->setConfiguration($config);
    }
}
