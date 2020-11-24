<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Exception;

use RuntimeException;

use function sprintf;

final class SchemaDumpRequiresNoMigrations extends RuntimeException implements ConsoleException
{
    public static function new(string $namespace): self
    {
        return new self(sprintf(
            'Delete any previous migrations in the namespace "%s" before dumping your schema.',
            $namespace
        ));
    }
}
