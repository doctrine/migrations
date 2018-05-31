<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Provider;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use UnexpectedValueException;
use function count;

/**
 * @internal
 */
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

        if (count($metadata) === 0) {
            throw new UnexpectedValueException('No mapping information to process');
        }

        $tool = new SchemaTool($this->entityManager);

        return $tool->getSchemaFromMetadata($metadata);
    }
}
