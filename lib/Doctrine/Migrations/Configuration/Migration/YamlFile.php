<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Migration;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\FileNotFound;
use Doctrine\Migrations\Configuration\Migration\Exception\YamlNotAvailable;
use Doctrine\Migrations\Configuration\Migration\Exception\YamlNotValid;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use function assert;
use function class_exists;
use function file_exists;
use function file_get_contents;
use function is_array;

final class YamlFile extends ConfigurationFile
{
    public function getConfiguration() : Configuration
    {
        if (! class_exists(Yaml::class)) {
            throw YamlNotAvailable::new();
        }

        if (! file_exists($this->file)) {
            throw FileNotFound::new($this->file);
        }

        $content = file_get_contents($this->file);

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
            $config['migrations_paths'] = $this->getDirectoriesRelativeToFile(
                $config['migrations_paths'],
                $this->file
            );
        }

        return (new ConfigurationArray($config))->getConfiguration();
    }
}
