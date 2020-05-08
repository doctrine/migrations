<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Exception\RollupFailed;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use function count;

/**
 * The Rollup class is responsible for deleting all previously executed migrations from the versions table
 * and marking the freshly dumped schema migration (that was created with SchemaDumper) as migrated.
 *
 * @internal
 */
class Rollup
{
    /** @var MigrationsRepository */
    private $migrationRepository;

    /** @var MetadataStorage */
    private $metadataStorage;

    public function __construct(
        MetadataStorage $metadataStorage,
        MigrationsRepository $migrationRepository
    ) {
        $this->migrationRepository = $migrationRepository;
        $this->metadataStorage     = $metadataStorage;
    }

    /**
     * @throws RollupFailed
     */
    public function rollup() : Version
    {
        $versions = $this->migrationRepository->getMigrations();

        if (count($versions) === 0) {
            throw RollupFailed::noMigrationsFound();
        }

        if (count($versions) > 1) {
            throw RollupFailed::tooManyMigrations();
        }

        $this->metadataStorage->reset();

        $result = new ExecutionResult($versions->getItems()[0]->getVersion());
        $this->metadataStorage->complete($result);

        return $result->getVersion();
    }
}
