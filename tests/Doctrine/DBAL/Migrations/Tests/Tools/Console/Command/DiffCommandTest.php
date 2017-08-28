<?php

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use org\bovigo\vfs\vfsStream;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Migrations\Provider\SchemaProviderInterface;
use Doctrine\DBAL\Migrations\Provider\StubSchemaProvider;
use Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand;

class DiffCommandTest extends CommandTestCase
{
    const VERSION = '20160705000000';

    private $root;
    private $migrationFile;

    public function testCommandCreatesNewMigrationsFileWithAVersionFromConfiguration()
    {
        $this->config->expects($this->once())
            ->method('generateVersionNumber')
            ->willReturn(self::VERSION);

        list($tester, $statusCode) = $this->executeCommand([]);

        $this->assertSame(0, $statusCode);
        $this->assertContains($this->migrationFile, $tester->getDisplay());
        $this->assertTrue($this->root->hasChild($this->migrationFile));
        $content = $this->root->getChild($this->migrationFile)->getContent();
        $this->assertContains('class Version' . self::VERSION, $content);
        $this->assertContains('CREATE TABLE example', $content);
    }

    protected function setUp()
    {
        parent::setUp();

        $this->migrationFile = sprintf('Version%s.php', self::VERSION);
        $this->root          = vfsStream::setup('migrations');
        $this->config->method('getMigrationsDirectory')
            ->willReturn(vfsStream::url('migrations'));
    }

    protected function createCommand()
    {
        $schema = new Schema();
        $t      = $schema->createTable('example');
        $t->addColumn('id', 'integer', ['autoincrement' => true]);
        $t->setPrimaryKey(['id']);

        return new DiffCommand(new StubSchemaProvider($schema));
    }
}
