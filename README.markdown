# Doctrine Database Migrations

## Status

[![Build Status](https://travis-ci.org/doctrine/migrations.svg)](https://travis-ci.org/doctrine/migrations)
[![Dependency Status](https://www.versioneye.com/php/doctrine:migrations/badge.svg)](https://www.versioneye.com/php/doctrine:migrations/)

## Eric Clemmons' Modifications

The latest official PHAR had path issues for me, so I made a couple of modifications and made
packaging a bit easier, especially when creating a custom PHAR for your own apps.

[Download `doctrine-migrations.phar` with custom Input/Output CLI support](http://github.com/downloads/ericclemmons/migrations/doctrine-migrations.phar)

### Modifications

* Added `DiffCommand` for migrations.
* Support for custom `ArgvInput` in CLI instance
* Support for custom `ConsoleOutput` in CLI instance

In the same way that Doctrine will attempt to load the return values from `migrations-db.php` as your
connection parameters, you can have `migrations-input.php` return:

    $input = new \Symfony\Component\Console\Input\ArgvInput;
    ... make some changes ...
    return $input;

or have `migrations-output.php` return a customized `ConsoleOutput` with support for HTML tags in
your SQL statements:

    $output = new \Symfony\Component\Console\Output\ConsoleOutput;
    $output->setStyle('p');
    return $output;

This should give you the flexibility you need for customizing your input/output in the CLI.

### Building Your Phar

Make sure Composer and all necessary dependencies are installed:

    curl -s https://getcomposer.org/installer | php
    php composer.phar install --dev

Make sure that the Box project is installed:

    curl -s http://box-project.org/installer.php | php

Build the PHAR archive:

    php box.phar build

The `doctrine-migrations.phar` archive is built in the `build` directory.

### Creating archive disabled by INI setting

If you receive an error that looks like:

    creating archive "build/doctrine-migrations.phar" disabled by INI setting

This can be fixed by setting the following in your php.ini:

    ; http://php.net/phar.readonly
    phar.readonly = Off

### Installing Dependencies

To install dependencies run a composer update:

    composer update

## Official Documentation

All available documentation can be found [here](http://docs.doctrine-project.org/projects/doctrine-migrations/en/latest/).
