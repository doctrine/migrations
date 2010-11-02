# Doctrine Database Migrations

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

Simply run `php package.php`, which will create the file: `build/doctrine-migrations.phar` for you.
Done! :)  This is a bit simpler than getting Phing/Ant going and running `phing build-migrations` and
hoping the rest of the build dependencies work.

## Official Documentation

All available documentation can be found [here](http://www.doctrine-project.org/projects/migrations/2.0/docs/en).
