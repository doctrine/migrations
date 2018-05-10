<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Configuration\Configuration;

/**
 * @var internal
 */
final class VersionFactory
{
    /** @var Configuration */
    private $configuration;

    /** @var VersionExecutorInterface */
    private $versionExecutor;

    public function __construct(Configuration $configuration, VersionExecutorInterface $versionExecutor)
    {
        $this->configuration   = $configuration;
        $this->versionExecutor = $versionExecutor;
    }

    public function createVersion(string $version, string $migrationClassName) : Version
    {
        return new Version(
            $this->configuration,
            $version,
            $migrationClassName,
            $this->versionExecutor
        );
    }
}
