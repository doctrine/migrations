<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration;

use Doctrine\Migrations\Configuration\Exception\YamlNotAvailable;
use Doctrine\Migrations\Configuration\Exception\YamlNotValid;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use function class_exists;
use function file_get_contents;
use function is_array;

/**
 * The YamlConfiguration class is responsible for loading migration configuration information from a YAML file.
 *
 * @internal
 */
class YamlConfiguration extends AbstractFileConfiguration
{
    /**
     * @inheritdoc
     */
    protected function doLoad(string $file) : void
    {
        if (! class_exists(Yaml::class)) {
            throw YamlNotAvailable::new();
        }

        try {
            $config = Yaml::parse(file_get_contents($file));
        } catch (ParseException $e) {
            throw YamlNotValid::malformed();
        }

        if (! is_array($config)) {
            throw YamlNotValid::invalid();
        }

        if (isset($config['migrations_directory'])) {
            $config['migrations_directory'] = $this->getDirectoryRelativeToFile(
                $file,
                $config['migrations_directory']
            );
        }

        $this->setConfiguration($config);
    }
}
