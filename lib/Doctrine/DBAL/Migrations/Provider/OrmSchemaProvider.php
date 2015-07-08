<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
*/

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
        if (!$this->isEntityManager($em)) {
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
