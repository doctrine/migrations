<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Provider;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;

use function usort;

/**
 * The OrmSchemaProvider class is responsible for creating a Doctrine\DBAL\Schema\Schema instance from the mapping
 * information provided by the Doctrine ORM. This is then used to diff against your current database schema to produce
 * a migration to bring your database in sync with the ORM mapping information.
 *
 * @internal
 */
final class OrmSchemaProvider implements SchemaProvider
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    public function createSchema(): Schema
    {
        /** @var array<int, ClassMetadata<object>> $metadata */
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        usort($metadata, static function (ClassMetadata $a, ClassMetadata $b): int {
            return $a->getTableName() <=> $b->getTableName();
        });

        $tool = new SchemaTool($this->entityManager);

        return $tool->getSchemaFromMetadata($metadata);
    }
}
