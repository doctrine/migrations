<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;

class CustomClassNameMigrationFactory implements MigrationFactory
{
    /** @param class-string<AbstractMigration> $migrationClassName */
    public function __construct(
        private readonly MigrationFactory $parentMigrationFactory,
        private readonly string $migrationClassName,
    ) {
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        return $this->parentMigrationFactory->createVersion($this->migrationClassName);
    }
}
