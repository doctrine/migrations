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

namespace Doctrine\DBAL\Migrations\Tests\Functional;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Doctrine\DBAL\Version as DbalVersion;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup as OrmSetup;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Migrations\MigrationsVersion;
use Doctrine\DBAL\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\DBAL\Migrations\Provider\SchemaProviderInterface;
use Doctrine\DBAL\Migrations\Provider\StubSchemaProvider;
use Doctrine\DBAL\Migrations\Tools\Console\Command as MigrationCommands;
use Doctrine\DBAL\Migrations\Tests\Helper;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;

/**
 * Tests the entire console application, end to end.
 */
class CliTest extends MigrationTestCase
{
    private $conn;

    private $application;

    private $lastExit;

    public function testMigrationLifecycleFromCommandLine()
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

    public function testGenerateCommandAddsNewVersion()
    {
        self::assertVersionCount(0, 'Should start with no versions');
        $this->executeCommand('migrations:generate');
        self::assertSuccessfulExit();
        self::assertVersionCount(1, 'generate command should add one version');

        $output = $this->executeCommand('migrations:status');
        self::assertSuccessfulExit();
        self::assertRegExp('/available migrations:\s+2/im', $output);
    }

    public function testGenerateCommandAddsNewMigrationOrganizedByYearAndMonth()
    {
        self::assertVersionCount(0, 'Should start with no versions');
        $this->executeCommand('migrations:generate', 'config_organize_by_year_and_month.xml');
        self::assertSuccessfulExit();
        self::assertVersionCount(1, 'generate command should add one version');

        $output = $this->executeCommand('migrations:status', 'config_organize_by_year_and_month.xml');
        self::assertSuccessfulExit();
        self::assertRegExp('/available migrations:\s+1/im', $output);
    }

    public function testMigrationDiffWritesNewMigrationWithExpectedSql()
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

    public function testMigrationDiffWritesNewMigrationWithFormattedSql()
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

    public function testMigrationDiffWithEntityManagerGeneratesMigrationFromEntities()
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

    public function testDiffCommandWithSchemaFilterOnlyWorksWithTablesThatMatchFilter()
    {
        if ($this->isDbalOld()) {
            $this->markTestSkipped(sprintf(
                'Schema filters were added in DBAL 2.2, version %s installed',
                DbalVersion::VERSION
            ));
        }

        $this->conn->getConfiguration()->setFilterSchemaAssetsExpression('/^bar$/');

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
     * @group regression
     */
    public function testDiffCommandSchemaFilterAreCaseSensitive()
    {
        if ($this->isDbalOld()) {
            $this->markTestSkipped(sprintf(
                'Schema filters were added in DBAL 2.2, version %s installed',
                DbalVersion::VERSION
            ));
        }

        $this->conn->getConfiguration()->setFilterSchemaAssetsExpression('/^FOO$/');

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

    protected function setUp()
    {
        $migrationsDbFilePath =
            __DIR__ . DIRECTORY_SEPARATOR . '_files ' . DIRECTORY_SEPARATOR . 'migrations.db';
        if (file_exists($migrationsDbFilePath)) {
            @unlink($migrationsDbFilePath);
        }
        Helper::deleteDir(
            __DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'migrations'
        );

        $this->conn        = $this->getSqliteConnection();
        $this->application = new Application('Doctrine Migrations Test', MigrationsVersion::VERSION());
        $this->application->setCatchExceptions(false);
        $this->application->setAutoExit(false);
        $this->application->getHelperSet()->set(
            new ConnectionHelper($this->conn),
            'connection'
        );
        $this->application->addCommands([
            new MigrationCommands\ExecuteCommand(),
            new MigrationCommands\GenerateCommand(),
            new MigrationCommands\LatestCommand(),
            new MigrationCommands\MigrateCommand(),
            new MigrationCommands\StatusCommand(),
            new MigrationCommands\VersionCommand(),
        ]);
    }

    protected function withDiffCommand(SchemaProviderInterface $provider = null)
    {
        $this->application->add(new MigrationCommands\DiffCommand($provider));
    }

    protected function executeCommand($commandName, $configFile = 'config.yml', array $args = [])
    {
        $input  = new ArrayInput(array_merge(
            [
                'command'         => $commandName,
                '--configuration' => __DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . $configFile,
            ],
            $args
        ));
        $output = $this->getOutputStream();

        $this->lastExit = $this->application->run($input, $output);

        return $this->getOutputStreamContent($output);
    }

    protected function assertSuccessfulExit($msg = '')
    {
        self::assertEquals(0, $this->lastExit, $msg);
    }

    protected function assertVersionCount($count, $msg = '')
    {
        self::assertCount($count, $this->findMigrations(), $msg);
    }

    protected function getSchema()
    {
        $s = new Schema();
        $t = $s->createTable('foo');
        $t->addColumn('id', 'integer', [
            'autoincrement' => true,
        ]);
        $t->setPrimaryKey(['id']);

        $t = $s->createTable('bar');
        $t->addColumn('id', 'integer', [
            'autoincrement' => true,
        ]);
        $t->setPrimaryKey(['id']);

        return $s;
    }

    protected function isDbalOld()
    {
        return DbalVersion::compare('2.2.0') > 0;
    }

    /**
     * @param string $namespace
     * @return array|\string[]
     */
    private function findMigrations($namespace = 'TestMigrations')
    {
        $finder = new RecursiveRegexFinder();

        return $finder->findMigrations(
            __DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'migrations',
            $namespace
        );
    }

    /**
     * @return string file content for latest version
     */
    private function getFileContentsForLatestVersion()
    {
        $versions = $this->findMigrations();
        self::assertCount(
            1,
            $versions,
            'This method is designed to work for one existing version, you have ' . count($versions) . ' versions'
        );

        $versionClassName      = reset($versions);
        $versionClassReflected = new \ReflectionClass($versionClassName);

        return file_get_contents($versionClassReflected->getFileName());
    }
}

class FirstMigration extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('CREATE TABLE foo (id INTEGER AUTO_INCREMENT, PRIMARY KEY (id))');
    }

    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE foo');
    }
}

class SampleEntity
{
    private $id;
}
