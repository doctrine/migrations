<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Provider;

use Doctrine\ORM\Mapping\ClassMetadataFactory as BaseMetadataFactoryAlias;
use Doctrine\Persistence\Mapping\ClassMetadata;

use function array_reverse;

class ClassMetadataFactory extends BaseMetadataFactoryAlias
{
    /**
     * @return ClassMetadata[]
     */
    public function getAllMetadata(): array
    {
        return array_reverse(parent::getAllMetadata());
    }
}
