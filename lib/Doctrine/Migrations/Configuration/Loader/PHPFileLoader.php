<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Loader;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\FileNotFound;
use Doctrine\Migrations\Configuration\Exception\InvalidConfigurationKey;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;

/**
 * The ArrayConfiguration class is responsible for loading migration configuration information from a PHP file.
 *
 * @internal
 */
class PHPFileLoader extends AbstractFileLoader
{
    /**
     * @var ArrayLoader
     */
    private $arrayLoader;

    public function __construct(ArrayLoader $arrayLoader = null)
    {
        $this->arrayLoader = $arrayLoader ?: new ArrayLoader();
    }

    public function load($file) : Configuration
    {
        if (!file_exists($file)) {
            throw FileNotFound::new();
        }
        $config = require $file;
        if ($config instanceof Configuration){
            return  $config;
        }

        assert(is_array($config));
        if (isset($config['migrations_paths'])) {
            $config['migrations_paths'] = $this->getDirectoryRelativeToFile(
                $file,
                $config['migrations_paths']
            );
        }

        return $this->arrayLoader->load($config);
    }
}
