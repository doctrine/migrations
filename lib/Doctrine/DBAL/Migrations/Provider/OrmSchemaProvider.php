<?php

namespace Doctrine\DBAL\Migrations\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * A schema provider that uses the doctrine ORM to generate schemas.
 *
 * @since   1.0.0-alpha3
 */
final class OrmSchemaProvider implements SchemaProviderInterface
{
    /**
     * @var     EntityManagerInterface
     */
    private $entityManager;

    public function __construct($em)
    {
        if ( ! $this->isEntityManager($em)) {
            throw new \InvalidArgumentException(sprintf(
                '$em is not a valid Doctrine ORM Entity Manager, got "%s"',
                is_object($em) ? get_class($em) : gettype($em)
            ));
        }

        $this->entityManager = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function createSchema()
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        if (empty($metadata)) {
            throw new \UnexpectedValueException('No mapping information to process');
        }

        $tool = new SchemaTool($this->entityManager);

        return $tool->getSchemaFromMetadata($metadata);
    }


    /**
     * Doctrine's EntityManagerInterface was introduced in version 2.4, since this
     * library allows those older version we need to be able to check for those
     * old ORM versions. Hence the helper method.
     *
     * No need to check to see if EntityManagerInterface exists first here, PHP
     * doesn't care.
     *
     * @param   mixed $manager Hopefully an entity manager, but it may be anything
     * @return  boolean
     */
    private function isEntityManager($manager)
    {
        return $manager instanceof EntityManagerInterface || $manager instanceof EntityManager;
    }
}
