<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\internal;

/**
 * @var internal
 */
class Factory
{
    /** @var Configuration */
    private $configuration;

    /** @var ExecutorInterface */
    private $versionExecutor;

    public function __construct(Configuration $configuration, ExecutorInterface $versionExecutor)
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
