<?php

namespace Doctrine\DBAL\Migrations\Tools\Console\Command;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Provider\OrmSchemaProvider;
use Doctrine\DBAL\Migrations\Provider\SchemaProviderInterface;
use Doctrine\DBAL\Version as DbalVersion;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for generate migration classes by comparing your current database schema
 * to your mapping information.
 */
class DiffCommand extends GenerateCommand
{
    /** @var SchemaProviderInterface */
    protected $schemaProvider;

    public function __construct(?SchemaProviderInterface $schemaProvider = null)
    {
        $this->schemaProvider = $schemaProvider;
        parent::__construct();
    }

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
            ->addOption('filter-expression', null, InputOption::VALUE_OPTIONAL, 'Tables which are filtered by Regular Expression.')
            ->addOption('formatted', null, InputOption::VALUE_NONE, 'Format the generated SQL.')
            ->addOption('line-length', null, InputOption::VALUE_OPTIONAL, 'Max line length of unformatted lines.', 120)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $isDbalOld     = (DbalVersion::compare('2.2.0') > 0);
        $configuration = $this->getMigrationConfiguration($input, $output);

        $this->loadCustomTemplate($configuration, $output);

        $conn       = $configuration->getConnection();
        $platform   = $conn->getDatabasePlatform();
        $filterExpr = $input->getOption('filter-expression');

        if ($filterExpr) {
            if ($isDbalOld) {
                throw new \InvalidArgumentException('The "--filter-expression" option can only be used as of Doctrine DBAL 2.2');
            }

            $conn->getConfiguration()
                ->setFilterSchemaAssetsExpression($filterExpr);
        }

        $fromSchema = $conn->getSchemaManager()->createSchema();
        $toSchema   = $this->getSchemaProvider()->createSchema();
        $filterExpr = $conn->getConfiguration()->getFilterSchemaAssetsExpression();

        //Not using value from options, because filters can be set from config.yml
        if (! $isDbalOld && $filterExpr) {
            foreach ($toSchema->getTables() as $table) {
                $tableName = $table->getName();
                if (preg_match($filterExpr, $this->resolveTableName($tableName))) {
                    continue;
                }

                $toSchema->dropTable($tableName);
            }
        }

        $up   = $this->buildCodeFromSql(
            $configuration,
            $fromSchema->getMigrateToSql($toSchema, $platform),
            $input->getOption('formatted'),
            $input->getOption('line-length')
        );
        $down = $this->buildCodeFromSql(
            $configuration,
            $fromSchema->getMigrateFromSql($toSchema, $platform),
            $input->getOption('formatted'),
            $input->getOption('line-length')
        );

        if (! $up && ! $down) {
            $output->writeln('No changes detected in your mapping information.');

            return;
        }

        $version = $configuration->generateVersionNumber();
        $path    = $this->generateMigration($configuration, $input, $version, $up, $down);

        $output->writeln(sprintf('Generated new migration class to "<info>%s</info>" from schema differences.', $path));
        $output->writeln(file_get_contents($path), OutputInterface::VERBOSITY_VERBOSE);
    }

    /**
     * @param string[] $sql
     */
    private function buildCodeFromSql(Configuration $configuration, array $sql, $formatted = false, $lineLength = 120)
    {
        $currentPlatform = $configuration->getConnection()->getDatabasePlatform()->getName();
        $code            = [];
        foreach ($sql as $query) {
            if (stripos($query, $configuration->getMigrationsTableName()) !== false) {
                continue;
            }

            if ($formatted) {
                if (! class_exists('\SqlFormatter')) {
                    throw new \InvalidArgumentException(
                        'The "--formatted" option can only be used if the sql formatter is installed.' .
                        'Please run "composer require jdorn/sql-formatter".'
                    );
                }

                $maxLength = $lineLength - 18 - 8; // max - php code length - indentation

                if (strlen($query) > $maxLength) {
                    $query = \SqlFormatter::format($query, false);
                }
            }

            $code[] = sprintf('$this->addSql(%s);', var_export($query, true));
        }

        if (! empty($code)) {
            array_unshift(
                $code,
                sprintf(
                    '$this->abortIf($this->connection->getDatabasePlatform()->getName() !== %s, %s);',
                    var_export($currentPlatform, true),
                    var_export(sprintf("Migration can only be executed safely on '%s'.", $currentPlatform), true)
                ),
                ''
            );
        }

        return implode("\n", $code);
    }

    private function getSchemaProvider()
    {
        if (! $this->schemaProvider) {
            $this->schemaProvider = new OrmSchemaProvider($this->getHelper('entityManager')->getEntityManager());
        }

        return $this->schemaProvider;
    }

    /**
     * Resolve a table name from its fully qualified name. The `$name` argument
     * comes from Doctrine\DBAL\Schema\Table#getName which can sometimes return
     * a namespaced name with the form `{namespace}.{tableName}`. This extracts
     * the table name from that.
     *
     * @param  string $name
     * @return string
     */
    private function resolveTableName($name)
    {
        $pos = strpos($name, '.');

        return $pos === false ? $name : substr($name, $pos + 1);
    }
}
