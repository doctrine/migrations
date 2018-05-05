<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use Doctrine\DBAL\Migrations\Provider\StubSchemaProvider;
use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\DBAL\Schema\Schema;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use function sprintf;

class DiffCommandTest extends CommandTestCase
{
    /** @var string */
    public const VERSION = '20160705000000';

    /** @var string */
    public const CUSTOM_RELATIVE_TEMPLATE_NAME = 'tests/Doctrine/DBAL/Migrations/Tests/Tools/Console/Command/_files/migration.tpl';

    /** @var string */
    public const CUSTOM_ABSOLUTE_TEMPLATE_NAME = __DIR__ . '/_files/migration.tpl';

    /** @var vfsStreamDirectory */
    private $root;

    /** @var string */
    private $migrationFile;

    public function testCommandCreatesNewMigrationsFileWithAVersionFromConfiguration() : void
    {
        $this->config->expects($this->once())
            ->method('generateVersionNumber')
            ->willReturn(self::VERSION);

        [$tester, $statusCode] = $this->executeCommand([]);

        self::assertSame(0, $statusCode);
        self::assertContains($this->migrationFile, $tester->getDisplay());
        self::assertTrue($this->root->hasChild($this->migrationFile));
        $content = $this->root->getChild($this->migrationFile)->getContent();
        self::assertContains('class Version' . self::VERSION, $content);
        self::assertContains('CREATE TABLE example', $content);
    }

    /** @return string[][] */
    public static function provideCustomTemplateNames() : array
    {
        return [
            'relativePath' => [self::CUSTOM_RELATIVE_TEMPLATE_NAME],
            'absolutePath' => [self::CUSTOM_ABSOLUTE_TEMPLATE_NAME],
        ];
    }

    /**
     * @dataProvider provideCustomTemplateNames
     */
    public function testCommandCreatesNewMigrationsFileWithAVersionAndACustomTemplateFromConfiguration(string $templateName) : void
    {
        $this->config->expects($this->once())
            ->method('generateVersionNumber')
            ->willReturn(self::VERSION);

        $this->config->expects($this->once())
            ->method('getCustomTemplate')
            ->willReturn($templateName);

        [$tester, $statusCode] = $this->executeCommand([]);

        self::assertSame(0, $statusCode);
        self::assertContains($this->migrationFile, $tester->getDisplay());
        self::assertTrue($this->root->hasChild($this->migrationFile));
        $content = $this->root->getChild($this->migrationFile)->getContent();
        self::assertContains('class Version' . self::VERSION, $content);
        self::assertContains('CREATE TABLE example', $content);
        self::assertContains('public function customTemplate()', $content);
    }

    protected function setUp() : void
    {
        parent::setUp();

        $this->migrationFile = sprintf('Version%s.php', self::VERSION);
        $this->root          = vfsStream::setup('migrations');
        $this->config->method('getMigrationsDirectory')
            ->willReturn(vfsStream::url('migrations'));
    }

    protected function createCommand() : AbstractCommand
    {
        $schema = new Schema();
        $t      = $schema->createTable('example');
        $t->addColumn('id', 'integer', ['autoincrement' => true]);
        $t->setPrimaryKey(['id']);

        return new DiffCommand(new StubSchemaProvider($schema));
    }
}
