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

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;

class SchemaManipulator
{
    private $platform;

    /** @var  AbstractSchemaManager */
    private $schemaManager;

    public function _construct(AbstractSchemaManager $schemaManager, $platform)
    {
        $this->schemaManager = $schemaManager;
        $this->platform = $platform;
    }

    /**
     * @return Schema
     */
    public function createFromSchema()
    {
        //var_dump($this->schemaManager);
        return $this->schemaManager->createSchema();
    }

    /**
     * @param Schema $fromSchema
     * @return Schema
     */
    public function createToSchema(Schema $fromSchema)
    {
        return clone $fromSchema;
    }

    /**
     * @param $fromSchema Schema
     * @param $toSchema Schema
     * @return array|string
     */
    public function getSqlDiffToMigrate($fromSchema, $toSchema)
    {
        return $fromSchema->getMigrateToSql($toSchema, $this->platform);
    }
}
