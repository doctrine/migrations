<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

interface VersionInterface
{
    public function getVersion() : string;
}
