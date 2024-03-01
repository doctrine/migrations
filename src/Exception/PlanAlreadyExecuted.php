<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Exception;

use RuntimeException;

final class PlanAlreadyExecuted extends RuntimeException implements MigrationException
{
    public static function new(): self
    {
        return new self('This plan was already marked as executed.');
    }
}
