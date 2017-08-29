<?php

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use org\bovigo\vfs\vfsStream;
use Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand;

class GenerateCommandTest extends CommandTestCase
{
    const VERSION              = '20160705000000';
    const CUSTOM_TEMPLATE_NAME = 'tests/Doctrine/DBAL/Migrations/Tests/Tools/Console/Command/_files/migration.tpl';

    private $root;
    private $migrationFile;

    public function testCommandCreatesNewMigrationsFileWithAVersionFromConfiguration()
    {
        $this->config->expects($this->once())
            ->method('generateVersionNumber')
            ->willReturn(self::VERSION);

        list($tester, $statusCode) = $this->executeCommand([]);

        self::assertSame(0, $statusCode);
        self::assertContains($this->migrationFile, $tester->getDisplay());
        self::assertTrue($this->root->hasChild($this->migrationFile));
        self::assertContains('class Version' . self::VERSION, $this->root->getChild($this->migrationFile)->getContent());
    }

    public function testCommandCreatesNewMigrationsFileWithACustomTemplateFromConfiguration()
    {
        $this->config->expects($this->once())
            ->method('generateVersionNumber')
            ->willReturn(self::VERSION);

        $this->config->expects($this->once())
            ->method('getCustomTemplate')
            ->willReturn(self::CUSTOM_TEMPLATE_NAME);

        list($tester, $statusCode) = $this->executeCommand([]);

        self::assertSame(0, $statusCode);
        self::assertContains($this->migrationFile, $tester->getDisplay());
        self::assertTrue($this->root->hasChild($this->migrationFile));
        self::assertContains('public function customTemplate()', $this->root->getChild($this->migrationFile)->getContent());
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
        return new GenerateCommand();
    }
}
