<?php

declare(strict_types=1);

use Doctrine\Migrations\Tools\Console\ConsoleRunner;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;

$autoloadFiles = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php'
];

$autoloader = false;

foreach ($autoloadFiles as $autoloadFile) {
    if (file_exists($autoloadFile)) {
        require_once $autoloadFile;
        $autoloader = true;
    }
}

if (!$autoloader) {
    if (extension_loaded('phar') && ($uri = Phar::running())) {
        echo 'The phar has been built without dependencies' . PHP_EOL;
    }

    die('vendor/autoload.php could not be found. Did you run `php composer.phar install`?');
}

// Support for using the Doctrine ORM convention of providing a `cli-config.php` file.
$directories = [getcwd(), getcwd() . DIRECTORY_SEPARATOR . 'config'];

$configFile = null;
foreach ($directories as $directory) {
    $configFile = $directory . DIRECTORY_SEPARATOR . 'cli-config.php';

    if (file_exists($configFile)) {
        break;
    }
}

$helperSet = null;
if (file_exists($configFile)) {
    if ( ! is_readable($configFile)) {
        trigger_error(
            'Configuration file [' . $configFile . '] does not have read permission.', E_USER_ERROR
        );
    }

    $helperSet = require $configFile;

    if ( ! ($helperSet instanceof HelperSet)) {
        foreach ($GLOBALS as $helperSetCandidate) {
            if ($helperSetCandidate instanceof HelperSet) {
                $helperSet = $helperSetCandidate;
                break;
            }
        }
    }
}

$helperSet = ($helperSet) ?: new HelperSet();

if(class_exists('\Symfony\Component\Console\Helper\QuestionHelper')) {
    $helperSet->set(new QuestionHelper(), 'question');
} else {
    $helperSet->set(new DialogHelper(), 'dialog');
}

$input = file_exists('migrations-input.php')
    ? include 'migrations-input.php'
    : null;

$output = file_exists('migrations-output.php')
    ? include 'migrations-output.php'
    : null;

$commands = [];

ConsoleRunner::run($helperSet, $commands);
