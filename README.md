# Doctrine Database Migrations

## Status

[![Build Status](https://travis-ci.org/doctrine/migrations.svg)](https://travis-ci.org/doctrine/migrations)
[![Dependency Status](https://www.versioneye.com/php/doctrine:migrations/badge.svg)](https://www.versioneye.com/php/doctrine:migrations/)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/doctrine/migrations/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/doctrine/migrations/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/doctrine/migrations/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/doctrine/migrations/?branch=master)


## Official Documentation

All available documentation can be found [here](https://www.doctrine-project.org/projects/doctrine-migrations/en/latest/index.html).

The the documentation is located in [the docs directory](https://github.com/doctrine/migrations/tree/master/docs).

## Working with Doctrine Migrations
    
### Using the integration of your framework

  * [Symfony 2+](https://packagist.org/packages/doctrine/doctrine-migrations-bundle)
  * [Zend Framework 2](https://packagist.org/packages/doctrine/doctrine-orm-module) 
  * [Laravel](https://packagist.org/packages/laravel-doctrine/migrations)
  * [Silex](https://packagist.org/packages/kurl/silex-doctrine-migrations-provider)
  * [Silex](https://packagist.org/packages/dbtlr/silex-doctrine-migrations)
  * [Nette](https://packagist.org/packages/zenify/doctrine-migrations)
  * others…

### Using Composer
            
```bash
composer require doctrine/migrations
```

### Downloading the latest PHAR release

You can download the PHAR archive directly on [the release page](https://github.com/doctrine/migrations/releases).

### Building Your own Phar

Make sure Composer and all necessary dependencies are installed:

```bash
curl -s https://getcomposer.org/installer | php
php composer.phar install
```

Build the PHAR archive:

```bash
./build-phar.sh
```

The `doctrine-migrations.phar` archive will now be present in the `build` directory.

## Installing dependencies

To install dependencies run a Composer update command:

```bash
composer update
```

## Running the unit tests

To run the tests, you need the PDO SQLite extension for PHP.

Running the tests from the project root:
```bash
vendor/bin/phpunit
```

Happy testing :-)
