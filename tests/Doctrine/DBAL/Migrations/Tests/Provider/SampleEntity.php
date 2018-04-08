<?php

namespace Doctrine\DBAL\Migrations\Tests\Provider;

class SampleEntity
{
    /** @var mixed */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}
