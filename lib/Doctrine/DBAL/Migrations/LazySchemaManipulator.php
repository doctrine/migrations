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

namespace Doctrine\DBAL\Migrations;

use Doctrine\DBAL\Schema\Schema;
use ProxyManager\Factory\LazyLoadingGhostFactory;
use ProxyManager\Proxy\LazyLoadingInterface;

class LazySchemaManipulator
{
    /** @var  LazyLoadingGhostFactory */
    private $proxyFactory;

    /** @var SchemaManipulator */
    private $originalSchemaManipulator;

    public function __construct(LazyLoadingGhostFactory $proxyFactory, SchemaManipulator $originalSchemaManipulator)
    {
        $this->proxyFactory = $proxyFactory;
        $this->originalSchemaManipulator = $originalSchemaManipulator;
    }

    /**
     * @return Schema
     */
    public function createFromSchema()
    {
        $originalSchemaManipulator = $this->originalSchemaManipulator;

        return $this->proxyFactory->createProxy(
            Schema::class,
            function (& $wrappedObject, $method, array $parameters, & $initializer) use ($originalSchemaManipulator) {
                $initializer   = null;
                $wrappedObject = $originalSchemaManipulator->createFromSchema();

                return true;
            }
        );
    }

    /**
     * @param Schema $fromSchema
     * @return Schema
     */
    public function createToSchema(Schema $fromSchema)
    {
        $originalSchemaManipulator = $this->originalSchemaManipulator;

        if ($fromSchema instanceof LazyLoadingInterface && ! $fromSchema->isProxyInitialized()) {
            return $this->proxyFactory->createProxy(
                Schema::class,
                function (& $wrappedObject, $method, array $parameters, & $initializer) use ($originalSchemaManipulator, $fromSchema) {
                    $initializer   = null;
                    $wrappedObject = $originalSchemaManipulator->createToSchema($fromSchema);

                    return true;
                }
            );
        }

        return $this->originalSchemaManipulator->createToSchema($fromSchema);
    }

    /**
     * @param Schema $fromSchema
     * @param Schema $toSchema
     *
     * @return array
     */
    public function getSqlDiffToMigrate(Schema $fromSchema,Schema $toSchema)
    {
        if (
            $toSchema instanceof LazyLoadingInterface
            && ! $toSchema->isProxyInitialized()
        ) {
            return [];
        }

        return $this->originalSchemaManipulator->getSqlDiffToMigrate($fromSchema, $toSchema);
    }
}
