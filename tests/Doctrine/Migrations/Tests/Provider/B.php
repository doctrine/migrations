<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Provider;

class B
{
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
