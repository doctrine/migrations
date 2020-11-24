<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Migration;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\FileNotFound;

use function assert;
use function file_exists;
use function is_array;

final class PhpFile extends ConfigurationFile
{
    public function getConfiguration(): Configuration
    {
        if (! file_exists($this->file)) {
            throw FileNotFound::new($this->file);
        }

        $config = require $this->file;
        if ($config instanceof Configuration) {
            return $config;
        }

        assert(is_array($config));
        if (isset($config['migrations_paths'])) {
            $config['migrations_paths'] = $this->getDirectoriesRelativeToFile(
                $config['migrations_paths'],
                $this->file
            );
        }

        return (new ConfigurationArray($config))->getConfiguration();
    }
}
