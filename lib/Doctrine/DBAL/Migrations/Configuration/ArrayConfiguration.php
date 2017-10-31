<?php

namespace Doctrine\DBAL\Migrations\Configuration;

/**
 * Load migration configuration information from a PHP configuration file.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @author      David Havl <contact@davidhavl.com>
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
