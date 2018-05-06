<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Provider;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use UnexpectedValueException;

final class OrmSchemaProvider implements SchemaProviderInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    public function createSchema() : Schema
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        if (empty($metadata)) {
            throw new UnexpectedValueException('No mapping information to process');
        }

        $tool = new SchemaTool($this->entityManager);

        return $tool->getSchemaFromMetadata($metadata);
    }
}
