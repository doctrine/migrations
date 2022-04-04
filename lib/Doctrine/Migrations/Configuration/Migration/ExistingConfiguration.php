<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Migration;

use Doctrine\Migrations\Configuration\Configuration;

final class ExistingConfiguration implements ConfigurationLoader
{
    private Configuration $configurations;

    public function __construct(Configuration $configurations)
    {
        $this->configurations = $configurations;
    }

    public function getConfiguration(): Configuration
    {
        return $this->configurations;
    }
}
