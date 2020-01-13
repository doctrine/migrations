<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Configuration;

use Doctrine\Migrations\Configuration\Configuration;

final class ExistingConfiguration implements ConfigurationLoader
{
    /** @var Configuration */
    private $configurations;

    public function __construct(Configuration $configurations)
    {
        $this->configurations = $configurations;
    }

    public function getConfiguration() : Configuration
    {
        return $this->configurations;
    }
}
