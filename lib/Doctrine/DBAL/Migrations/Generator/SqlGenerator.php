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

namespace Doctrine\DBAL\Migrations\Generator;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Generates platform-specific SQL migrations
 *
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Tyler Sommer <sommertm@gmail.com>
 */
class SqlGenerator implements GeneratorInterface
{
    /**
     * @var Doctrine\DBAL\Migrations\Configuration\Configuration
     */
    protected $configuration;

    /**
     * Constructor
     *
     * @param Doctrine\DBAL\Migrations\Configuration\Configuration A Migration configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Generates a migration using a SchemaDiff
     *
     * @param Schema $fromSchema
     * @param Schema $toSchema
     *
     * @return string Raw PHP code to be used as the body of a Migration
     */
    public function generateMigration(Schema $fromSchema, Schema $toSchema)
    {
        $platform = $this->configuration->getConnection()->getDatabasePlatform();
        $sql = $fromSchema->getMigrateToSql($toSchema, $platform);

        $code = array(
            "\$this->abortIf(\$this->connection->getDatabasePlatform()->getName() != \"" . $platform->getName() . "\");", "",
        );
        foreach ($sql as $query) {
            if (strpos($query, $this->configuration->getMigrationsTableName()) !== false) {
                continue;
            }
            $code[] = "\$this->addSql(\"$query\");";
        }

        return implode("\n", $code);
    }
}
