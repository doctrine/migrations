<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsSet;
use Doctrine\Migrations\Version\Version;

interface MigrationsRepository
{
    public function hasMigration(string $version): bool;

    public function getMigration(Version $version): AvailableMigration;

    public function getMigrations(): AvailableMigrationsSet;
}
