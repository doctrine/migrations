<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Migration;

use Doctrine\Migrations\Configuration\Configuration;

final class ExistingConfiguration implements ConfigurationLoader
{
    public function __construct(private readonly Configuration $configurations)
    {
    }

    public function getConfiguration(): Configuration
    {
        return $this->configurations;
    }
}
