<?php

namespace Doctrine\DBAL\Migrations\Tests;

class AllTests
{
    static public function suite()
    {
        $suite = new \PHPUnit_Framework_TestSuite("Doctrine\DBAL\Migrations Testsuite");

        return $suite;
    }
}