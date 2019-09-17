<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Loader;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\FileNotFound;
use Doctrine\Migrations\Configuration\Exception\InvalidConfigurationKey;
use Doctrine\Migrations\Configuration\Exception\JsonNotValid;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;

/**
 * The ArrayConfiguration class is responsible for loading migration configuration information from a PHP file.
 *
 * @internal
 */
class JsonFileLoader extends AbstractFileLoader
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

        $contents = file_get_contents($file);

        assert($contents !== false);

        $config = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw JsonNotValid::new();
        }

        if (isset($config['migrations_paths'])) {
            $config['migrations_paths'] = $this->getDirectoryRelativeToFile(
                $file,
                $config['migrations_paths']
            );
        }

        return $this->arrayLoader->load($config);
    }
}
