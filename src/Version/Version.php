<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use Stringable;

final class Version implements Stringable
{
    public function __construct(private readonly string $version)
    {
    }

    public function __toString(): string
    {
        return $this->version;
    }

    public function equals(mixed $object): bool
    {
        return $object instanceof self && $object->version === $this->version;
    }
}
