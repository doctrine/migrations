<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Tests\Provider;

class SampleEntity
{
    /** @var null|int */
    private $id;

    public function getId() : ?int
    {
        return $this->id;
    }
}
