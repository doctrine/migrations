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

    const onMigrationsMigrating        = 'onMigrationsMigrating';
    const onMigrationsMigrated         = 'onMigrationsMigrated';
    const onMigrationsVersionExecuting = 'onMigrationsVersionExecuting';
    const onMigrationsVersionExecuted  = 'onMigrationsVersionExecuted';
    const onMigrationsVersionSkipped   = 'onMigrationsVersionSkipped';
}
