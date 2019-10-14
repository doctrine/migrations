<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Loader;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\FileNotFound;
use Doctrine\Migrations\Configuration\Exception\YamlNotAvailable;
use Doctrine\Migrations\Configuration\Exception\YamlNotValid;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use function assert;
use function class_exists;
use function file_exists;
use function file_get_contents;
use function is_array;

/**
 * @internal
 */
final class YamlFileLoader extends AbstractFileLoader
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
        if (! class_exists(Yaml::class)) {
            throw YamlNotAvailable::new();
        }

        if (! file_exists($file)) {
            throw FileNotFound::new();
        }

        $content = file_get_contents($file);

        assert($content !== false);

        try {
            $config = Yaml::parse($content);
        } catch (ParseException $e) {
            throw YamlNotValid::malformed();
        }

        if (! is_array($config)) {
            throw YamlNotValid::invalid();
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
