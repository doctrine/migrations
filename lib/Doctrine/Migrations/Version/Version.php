<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

final class Version
{
    private string $version;

    public function __construct(string $version)
    {
        $this->version = $version;
    }

    public function __toString(): string
    {
        return $this->version;
    }

    /**
     * @param mixed $object
     */
    public function equals($object): bool
    {
        return $object instanceof self && $object->version === $this->version;
    }
}
