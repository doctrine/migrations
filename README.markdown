# Doctrine Database Migrations

## Official Documentation

**Read the latest documentation at the [Doctrine Migrations](http://www.doctrine-project.org/projects/migrations) website**

## Features

* Single PHAR file for your projects
* *Diff*ing pending migrations (if an Entity Manager is available)
* Custom `ArgvInput` support in CLI
* Custom `ConsoleOutput` support in CLI


## Building the PHAR

    $   php package.php

(Creates `./build/doctrine-migrations.phar`)

## [Configuration](http://www.doctrine-project.org/projects/migrations/2.0/docs/reference/introduction/en#configuration)

### migrations.yml

Define how migrations will be stored and tracked within your database:

    ---
    name: Doctrine Sandbox Migrations
    migrations_namespace: DoctrineMigrations
    table_name: doctrine_migration_versions
    migrations_directory: /path/to/migrations/classes/DoctrineMigrations

### migrations-db.php

Define how to connect to your database:

    // migrations-db.php

    ...

    return array(
        'driver'    => 'pdo_mysql',
        'host'      => 'localhost',
        'user'      => 'migrations',
        'password'  => 'm1gr@t10n$',
        'dbname'    => 'doctrine_sandbox'
    );

### migrations-input.php (Optional)

Specify defaults or provide your own custom `ArgvInput`, should you so desire:

    $input = new \Symfony\Component\Console\Input\ArgvInput;
    ... make some changes ...
    return $input;

### migrations-output.php (Optional)

If your database migrations contain HTML, you may run into issues with outputting the SQL to the console.
This is because the `ConsoleOutput` class uses HTML-like tags for styling certain messages, such as `error`s,
`info` messages, etc.

For HTML to render properly, you can customize the `ConsoleOutput` within this file as follows:

    $output = new \Symfony\Component\Console\Output\ConsoleOutput;
    $output->setStyle('p'); // Adds default styling to the 'p' tag, as to not throw a rendering exception

    return $output;
