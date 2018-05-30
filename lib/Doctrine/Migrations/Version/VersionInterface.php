<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

interface VersionInterface
{
    public function getVersion() : string;
}
