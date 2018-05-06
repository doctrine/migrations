<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

final class Events
{
    public const onMigrationsMigrating        = 'onMigrationsMigrating';
    public const onMigrationsMigrated         = 'onMigrationsMigrated';
    public const onMigrationsVersionExecuting = 'onMigrationsVersionExecuting';
    public const onMigrationsVersionExecuted  = 'onMigrationsVersionExecuted';
    public const onMigrationsVersionSkipped   = 'onMigrationsVersionSkipped';
}
