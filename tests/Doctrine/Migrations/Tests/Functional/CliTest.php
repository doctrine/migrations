<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Functional;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\Migrations\Provider\SchemaProviderInterface;
use Doctrine\Migrations\Provider\StubSchemaProvider;
use Doctrine\Migrations\Tests\Helper;
use Doctrine\Migrations\Tests\MigrationTestCase;
use Doctrine\Migrations\Tools\Console\Command as MigrationCommands;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\ORM\Tools\Setup as OrmSetup;
use PackageVersions\Versions;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use const DIRECTORY_SEPARATOR;
use function array_merge;
use function assert;
use function count;
use function file_exists;
use function file_get_contents;
use function preg_match;
use function reset;
use function unlink;

/**
 * Tests the entire console application, end to end.
 */
class CliTest extends MigrationTestCase
{
    /** @var Connection */
    private $conn;

    /** @var Application */
    private $application;

    /** @var int|null */
    private $lastExit;

    public function testDumpSchema() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Delete any previous migrations before dumping your schema.');

        $this->executeCommand('migrations:dump-schema');
    }

    public function testRollup() : void
    {
        $output = $this->executeCommand('migrations:rollup');

        self::assertRegExp('/Rolled up migrations to version 20150426000000/', $output);
    }

    public function testMigrationLifecycleFromCommandLine() : void
    {
        $output = $this->executeCommand('migrations:status');
        self::assertSuccessfulExit();
        self::assertRegExp('/available migrations:\s+1/im', $output);
        self::assertRegExp('/new migrations:\s+1/im', $output);

        $output = $this->executeCommand('migrations:latest');
        self::assertContains('20150426000000', $output);

        $this->executeCommand('migrations:migrate', 'config.yml', ['--no-interaction']);
        self::assertSuccessfulExit();

        $output = $this->executeCommand('migrations:status');
        self::assertSuccessfulExit();
        self::assertRegExp('/^.*available migrations:\s+1/im', $output);
        self::assertRegExp('/^.*new migrations:\s+0/im', $output);
    }

    public function testGenerateCommandAddsNewVersion() : void
    {
        self::assertVersionCount(0, 'Should start with no versions');
        $this->executeCommand('migrations:generate');
        self::assertSuccessfulExit();
        self::assertVersionCount(1, 'generate command should add one version');

        $output = $this->executeCommand('migrations:status');
        self::assertSuccessfulExit();
        self::assertRegExp('/available migrations:\s+2/im', $output);
    }

    public function testGenerateCommandAddsNewMigrationOrganizedByYearAndMonth() : void
    {
        self::assertVersionCount(0, 'Should start with no versions');
        $this->executeCommand('migrations:generate', 'config_organize_by_year_and_month.xml');
        self::assertSuccessfulExit();
        self::assertVersionCount(1, 'generate command should add one version');

        $output = $this->executeCommand('migrations:status', 'config_organize_by_year_and_month.xml');
        self::assertSuccessfulExit();
        self::assertRegExp('/available migrations:\s+1/im', $output);
    }

    public function testMigrationDiffWritesNewMigrationWithExpectedSql() : void
    {
        $this->withDiffCommand(new StubSchemaProvider($this->getSchema()));
        self::assertVersionCount(0, 'should start with no versions');
        $this->executeCommand('migrations:diff');
        self::assertSuccessfulExit();
        self::assertVersionCount(1, 'diff command should add one version');

        $output = $this->executeCommand('migrations:status');
        self::assertSuccessfulExit();
        self::assertRegExp('/available migrations:\s+2/im', $output);

        $versionClassContents = $this->getFileContentsForLatestVersion();

        self::assertContains('CREATE TABLE bar', $versionClassContents);
        self::assertContains('DROP TABLE bar', $versionClassContents);
    }

    public function testMigrationDiffWritesNewMigrationWithFormattedSql() : void
    {
        $this->withDiffCommand(new StubSchemaProvider($this->getSchema()));
        self::assertVersionCount(0, 'should start with no versions');
        $this->executeCommand(
            'migrations:diff',
            'config.yml',
            [
                '--formatted' => null,
                '--line-length' => 50,
            ]
        );
        self::assertSuccessfulExit();
        self::assertVersionCount(1, 'diff command should add one version');

        $output = $this->executeCommand('migrations:status');
        self::assertSuccessfulExit();
        self::assertRegExp('/available migrations:\s+2/im', $output);

        $versionClassContents = $this->getFileContentsForLatestVersion();

        self::assertContains("CREATE TABLE foo (\n", $versionClassContents);
        self::assertContains('DROP TABLE bar', $versionClassContents);
    }

    public function testMigrationDiffWithEntityManagerGeneratesMigrationFromEntities() : void
    {
        $config        = OrmSetup::createXMLMetadataConfiguration([__DIR__ . '/_files/entities'], true);
        $entityManager = EntityManager::create($this->conn, $config);
        $this->application->getHelperSet()->set(
            new EntityManagerHelper($entityManager),
            'em'
        );
        $this->withDiffCommand();

        self::assertVersionCount(0, 'should start with no versions');
        $this->executeCommand('migrations:diff');
        self::assertSuccessfulExit();
        self::assertVersionCount(1, 'diff command should add one version');

        $output = $this->executeCommand('migrations:status');
        self::assertSuccessfulExit();
        self::assertRegExp('/available migrations:\s+2/im', $output);

        $versionClassContents = $this->getFileContentsForLatestVersion();
        self::assertContains('CREATE TABLE sample_entity', $versionClassContents);
        self::assertContains('DROP TABLE sample_entity', $versionClassContents);
    }

    public function testDiffCommandWithSchemaFilterOnlyWorksWithTablesThatMatchFilter() : void
    {
        $this->conn->getConfiguration()->setSchemaAssetsFilter(
            static function ($assetName) {
                if ($assetName instanceof AbstractAsset) {
                    $assetName = $assetName->getName();
                }

                return preg_match('/^bar$/', $assetName);
            }
        );

        $this->withDiffCommand(new StubSchemaProvider($this->getSchema()));
        self::assertVersionCount(0, 'should start with no versions');
        $this->executeCommand('migrations:diff');
        self::assertSuccessfulExit();
        self::assertVersionCount(1, 'diff command should add one version');

        $versionClassContents = $this->getFileContentsForLatestVersion();
        self::assertContains('CREATE TABLE bar', $versionClassContents);
        self::assertContains('DROP TABLE bar', $versionClassContents);
        self::assertNotContains(
            'CREATE TABLE foo',
            $versionClassContents,
            'should ignore the "foo" table due to schema asset filter'
        );
    }

    /**
     * @see https://github.com/doctrine/migrations/issues/179
     *
     * @group regression
     */
    public function testDiffCommandSchemaFilterAreCaseSensitive() : void
    {
        $this->conn->getConfiguration()->setSchemaAssetsFilter(
            static function ($assetName) {
                if ($assetName instanceof AbstractAsset) {
                    $assetName = $assetName->getName();
                }

                return preg_match('/^FOO$/', $assetName);
            }
        );

        $schema = new Schema();
        $t      = $schema->createTable('FOO');
        $t->addColumn('id', 'integer', ['autoincrement' => true]);
        $t->setPrimaryKey(['id']);

        $this->withDiffCommand(new StubSchemaProvider($schema));
        self::assertVersionCount(0, 'should start with no versions');
        $output = $this->executeCommand('migrations:diff');
        self::assertSuccessfulExit();
        self::assertVersionCount(1, 'diff command should add one version');

        $versionClassContents = $this->getFileContentsForLatestVersion();
        self::assertContains('CREATE TABLE FOO', $versionClassContents);
        self::assertContains('DROP TABLE FOO', $versionClassContents);
    }

    protected function setUp() : void
    {
        $this->markTestSkipped();
        $migrationsDbFilePath =
            __DIR__ . DIRECTORY_SEPARATOR . '_files ' . DIRECTORY_SEPARATOR . 'migrations.db';
        if (file_exists($migrationsDbFilePath)) {
            @unlink($migrationsDbFilePath);
        }
        Helper::deleteDir(
            __DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'migrations'
        );

        $this->conn        = $this->getSqliteConnection();
        $this->application = new Application('Doctrine Migrations Test', Versions::getVersion('doctrine/migrations'));
        $this->application->setCatchExceptions(false);
        $this->application->setAutoExit(false);
        $this->application->getHelperSet()->set(
            new ConnectionHelper($this->conn),
            'connection'
        );
        $this->application->addCommands([
            new MigrationCommands\DumpSchemaCommand(),
            new MigrationCommands\ExecuteCommand(),
            new MigrationCommands\GenerateCommand(),
            new MigrationCommands\LatestCommand(),
            new MigrationCommands\MigrateCommand(),
            new MigrationCommands\RollupCommand(),
            new MigrationCommands\StatusCommand(),
            new MigrationCommands\VersionCommand(),
        ]);
    }

    protected function withDiffCommand(?SchemaProviderInterface $provider = null) : void
    {
        $this->application->add(new MigrationCommands\DiffCommand($provider));
    }

    /**
     * @param mixed[] $args
     */
    protected function executeCommand(
        string $commandName,
        string $configFile = 'config.yml',
        array $args = []
    ) : string {
        $input = new ArrayInput(array_merge(
            [
                'command'         => $commandName,
                '--no-ansi'       => null,
                '--configuration' => __DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . $configFile,
            ],
            $args
        ));

        $output = $this->getOutputStream();

        $this->lastExit = $this->application->run($input, $output);

        return $this->getOutputStreamContent($output);
    }

    protected function assertSuccessfulExit(string $msg = '') : void
    {
        self::assertSame(0, $this->lastExit, $msg);
    }

    protected function assertVersionCount(int $count, string $msg = '') : void
    {
        self::assertCount($count, $this->findMigrations(), $msg);
    }

    protected function getSchema() : Schema
    {
        $schema = new Schema();

        $table = $schema->createTable('foo');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);

        $table = $schema->createTable('bar');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);

        return $schema;
    }

    /**
     * @return string[]
     */
    private function findMigrations() : array
    {
        $finder = new RecursiveRegexFinder();

        return $finder->findMigrations(
            __DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'migrations'
        );
    }

    /**
     * @return string file content for latest version
     */
    private function getFileContentsForLatestVersion() : string
    {
        $versions = $this->findMigrations();
        self::assertCount(
            1,
            $versions,
            'This method is designed to work for one existing version, you have ' . count($versions) . ' versions'
        );

        $versionClassName      = reset($versions);
        $versionClassReflected = new ReflectionClass($versionClassName);

        $fileName = $versionClassReflected->getFileName();

        assert($fileName !== false);

        $contents = file_get_contents($fileName);

        assert($contents !== false);

        return $contents;
    }
}

class FirstMigration extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE foo (id INTEGER AUTO_INCREMENT, PRIMARY KEY (id))');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE foo');
    }
}

class SampleEntity
{
    /** @var int|null */
    private $id;

    public function getId() : ?int
    {
        return $this->id;
    }
}
