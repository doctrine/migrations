<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Tools\Console\ConsoleRunner;
use Phar;
use const DIRECTORY_SEPARATOR;
use const E_USER_ERROR;
use const PHP_EOL;
use function extension_loaded;
use function file_exists;
use function getcwd;
use function is_readable;
use function trigger_error;

(static function () : void {
    $autoloadFiles = [
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../../../autoload.php',
    ];

    $autoloaderFound = false;

    foreach ($autoloadFiles as $autoloadFile) {
        if (! file_exists($autoloadFile)) {
            continue;
        }

        require_once $autoloadFile;
        $autoloaderFound = true;
    }

    if (! $autoloaderFound) {
        if (extension_loaded('phar') && Phar::running() !== '') {
            echo 'The PHAR was built without dependencies!' . PHP_EOL;
            exit(1);
        }

        echo 'vendor/autoload.php could not be found. Did you run `composer install`?', PHP_EOL;
        exit(1);
    }

    // Support for using the Doctrine ORM convention of providing a `cli-config.php` file.
    $configurationDirectories = [
        getcwd(),
        getcwd() . DIRECTORY_SEPARATOR . 'config',
    ];

    $configurationFile = null;
    foreach ($configurationDirectories as $configurationDirectory) {
        $configurationFilePath = $configurationDirectory . DIRECTORY_SEPARATOR . 'cli-config.php';

        if (! file_exists($configurationFilePath)) {
            continue;
        }

        $configurationFile = $configurationFilePath;
        break;
    }

    if ($configurationFile !== null) {
        if (! is_readable($configurationFile)) {
            trigger_error('Configuration file [' . $configurationFile . '] does not have read permission.', E_USER_ERROR);
            exit(1);
        }

        require $configurationFile;
    }

    ConsoleRunner::run();
})();
