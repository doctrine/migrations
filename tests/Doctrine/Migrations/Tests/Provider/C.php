<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Provider;

class C
{
    /** @var int|null */
    private $id;

    public function getId(): ?int
    {
        return $this->id;
    }
}
