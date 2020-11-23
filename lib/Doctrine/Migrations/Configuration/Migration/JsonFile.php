<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Migration;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\FileNotFound;
use Doctrine\Migrations\Configuration\Migration\Exception\JsonNotValid;

use function assert;
use function file_exists;
use function file_get_contents;
use function json_decode;
use function json_last_error;

use const JSON_ERROR_NONE;

final class JsonFile extends ConfigurationFile
{
    public function getConfiguration(): Configuration
    {
        if (! file_exists($this->file)) {
            throw FileNotFound::new($this->file);
        }

        $contents = file_get_contents($this->file);

        assert($contents !== false);

        $config = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw JsonNotValid::new();
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
