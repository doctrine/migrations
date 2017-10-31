<?php

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

    if ( ! ($helperSet instanceof \Symfony\Component\Console\Helper\HelperSet)) {
        foreach ($GLOBALS as $helperSetCandidate) {
            if ($helperSetCandidate instanceof \Symfony\Component\Console\Helper\HelperSet) {
                $helperSet = $helperSetCandidate;
                break;
            }
        }
    }
}

$helperSet = ($helperSet) ?: new \Symfony\Component\Console\Helper\HelperSet();

if(class_exists('\Symfony\Component\Console\Helper\QuestionHelper')) {
    $helperSet->set(new \Symfony\Component\Console\Helper\QuestionHelper(), 'question');
} else {
    $helperSet->set(new \Symfony\Component\Console\Helper\DialogHelper(), 'dialog');
}


$input = file_exists('migrations-input.php')
       ? include 'migrations-input.php' : null;

$output = file_exists('migrations-output.php')
        ? include 'migrations-output.php' : null;

$cli = \Doctrine\DBAL\Migrations\Tools\Console\ConsoleRunner::createApplication($helperSet);
$cli->run($input, $output);

