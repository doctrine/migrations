<?php

namespace Doctrine\DBAL\Migrations\Tests;

require_once __DIR__ . '/../../../../TestInit.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'AllTests::main');
}

class AllTests
{
    public static function main()
    {
        \PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new \PHPUnit_Framework_TestSuite('Doctrine Tests');

        $directory = new \RecursiveDirectoryIterator(__DIR__);
        $iterator = new \RecursiveIteratorIterator($directory);
        $regex = new \RegexIterator($iterator, '/^.+\Test.php$/i', \RecursiveRegexIterator::GET_MATCH);
        foreach ($regex as $file) {
            $suite->addTestFile($file[0]);
        }

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'AllTests::main') {
    AllTests::main();
}