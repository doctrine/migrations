<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Tools\Console\ConsoleRunner;
use Phar;

use function extension_loaded;
use function file_exists;
use function fwrite;

use const PHP_EOL;
use const STDERR;

(static function (): void {
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
            fwrite(STDERR, 'The PHAR was built without dependencies!' . PHP_EOL);
            exit(1);
        }

        fwrite(STDERR, 'vendor/autoload.php could not be found. Did you run `composer install`?' . PHP_EOL);
        exit(1);
    }

    $dependencyFactory = ConsoleRunner::findDependencyFactory();

    ConsoleRunner::run([], $dependencyFactory);
})();
