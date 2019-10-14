<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Loader;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\FileNotFound;
use function assert;
use function file_exists;
use function is_array;

/**
 * @internal
 */
final class PhpFileLoader extends AbstractFileLoader
{
    /** @var ArrayLoader */
    private $arrayLoader;

    public function __construct()
    {
        $this->arrayLoader = new ArrayLoader();
    }

    /**
     * @param mixed $file
     */
    public function load($file) : Configuration
    {
        if (! file_exists($file)) {
            throw FileNotFound::new();
        }
        $config = require $file;
        if ($config instanceof Configuration) {
            return $config;
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
