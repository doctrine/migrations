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

### Creating archive disabled by INI setting

If you receive an error that looks like:

    creating archive "build/doctrine-migrations.phar" disabled by INI setting

This can be fixed by setting the following in your php.ini:

    ; http://php.net/phar.readonly
    phar.readonly = Off

### Installing Dependencies

To install dependencies issue the following commands:

    git submodule init
    git submodule update

## Official Documentation

All available documentation can be found [here](http://docs.doctrine-project.org/projects/doctrine-migrations/en/latest/).
