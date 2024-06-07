<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Exception;

use LogicException;

final class FrozenMigration extends LogicException implements MigrationException
{
    public static function new(): self
    {
        return new self('The migration is frozen and cannot be edited anymore.');
    }
}
