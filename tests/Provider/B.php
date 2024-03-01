<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Provider;

class B
{
    private int|null $id = null;

    public function getId(): int|null
    {
        return $this->id;
    }
}
