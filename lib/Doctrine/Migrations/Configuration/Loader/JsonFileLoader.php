<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Loader;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\FileNotFound;
use Doctrine\Migrations\Configuration\Exception\JsonNotValid;
use const JSON_ERROR_NONE;
use function assert;
use function file_exists;
use function file_get_contents;
use function json_decode;
use function json_last_error;

/**
 * @internal
 */
final class JsonFileLoader extends AbstractFileLoader
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
