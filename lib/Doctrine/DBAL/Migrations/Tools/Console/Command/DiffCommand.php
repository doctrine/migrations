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

namespace Doctrine\DBAL\Migrations\Tools\Console\Command;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Doctrine\ORM\Tools\SchemaTool,
    Doctrine\DBAL\Version as DbalVersion,
    Doctrine\DBAL\Migrations\Configuration\Configuration;

/**
 * Command for generate migration classes by comparing your current database schema
 * to your mapping information.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class DiffCommand extends GenerateCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('migrations:diff')
            ->setDescription('Generate a migration by comparing your current database to your mapping information.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command generates a migration by comparing your current database to your mapping information:

    <info>%command.full_name%</info>

You can optionally specify a <comment>--editor-cmd</comment> option to open the generated file in your favorite editor:

    <info>%command.full_name% --editor-cmd=mate</info>
EOT
            )
            ->addOption('filter-expression', null, InputOption::VALUE_OPTIONAL, 'Tables which are filtered by Regular Expression.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $isDbalOld = (DbalVersion::compare('2.2.0') > 0);
        $configuration = $this->getMigrationConfiguration($input, $output);

        $em = $this->getHelper('em')->getEntityManager();
        $conn = $em->getConnection();
        $platform = $conn->getDatabasePlatform();
        $metadata = $em->getMetadataFactory()->getAllMetadata();

        if (empty($metadata)) {
            $output->writeln('No mapping information to process.', 'ERROR');

            return;
        }

        if ($filterExpr = $input->getOption('filter-expression')) {
            if ($isDbalOld) {
                throw new \InvalidArgumentException('The "--filter-expression" option can only be used as of Doctrine DBAL 2.2');
            }

            $conn->getConfiguration()
                ->setFilterSchemaAssetsExpression($filterExpr);
        }

        $tool = new SchemaTool($em);

        $fromSchema = $conn->getSchemaManager()->createSchema();
        $toSchema = $tool->getSchemaFromMetadata($metadata);

        //Not using value from options, because filters can be set from config.yml
        if ( ! $isDbalOld && $filterExpr = $conn->getConfiguration()->getFilterSchemaAssetsExpression()) {
            $tableNames = $toSchema->getTableNames();
            foreach ($tableNames as $tableName) {
                $tableName = substr($tableName, strpos($tableName, '.') + 1);
                if ( ! preg_match($filterExpr, $tableName)) {
                    $toSchema->dropTable($tableName);
                }
            }
        }

        $up = $this->buildCodeFromSql($configuration, $fromSchema->getMigrateToSql($toSchema, $platform));
        $down = $this->buildCodeFromSql($configuration, $fromSchema->getMigrateFromSql($toSchema, $platform));

        if ( ! $up && ! $down) {
            $output->writeln('No changes detected in your mapping information.', 'ERROR');

            return;
        }

        $version = date('YmdHis');
        $path = $this->generateMigration($configuration, $input, $version, $up, $down);

        $output->writeln(sprintf('Generated new migration class to "<info>%s</info>" from schema differences.', $path));
    }

    private function buildCodeFromSql(Configuration $configuration, array $sql)
    {
        $currentPlatform = $configuration->getConnection()->getDatabasePlatform()->getName();
        $code = array(
            "\$this->abortIf(\$this->connection->getDatabasePlatform()->getName() != \"$currentPlatform\", \"Migration can only be executed safely on '$currentPlatform'.\");", "",
        );
        foreach ($sql as $query) {
            if (strpos($query, $configuration->getMigrationsTableName()) !== false) {
                continue;
            }
            $code[] = "\$this->addSql(\"$query\");";
        }

        return implode("\n", $code);
    }
}
