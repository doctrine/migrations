<?php

namespace Doctrine\DBAL\Migrations;

final class Events
{
    /**
     * Private constructor. This class cannot be instantiated.
     */
    private function __construct()
    {
    }

    public const onMigrationsMigrating        = 'onMigrationsMigrating';
    public const onMigrationsMigrated         = 'onMigrationsMigrated';
    public const onMigrationsVersionExecuting = 'onMigrationsVersionExecuting';
    public const onMigrationsVersionExecuted  = 'onMigrationsVersionExecuted';
    public const onMigrationsVersionSkipped   = 'onMigrationsVersionSkipped';
}
