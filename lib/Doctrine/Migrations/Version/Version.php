<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

class Version
{
    /** @var string */
    private $version;

    public function __construct(string $version)
    {
        $this->version = $version;
    }

    public function __toString()
    {
        return $this->version;
    }
}
