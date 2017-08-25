<?php

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use org\bovigo\vfs\vfsStream;
use Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand;

class GenerateCommandTest extends CommandTestCase
{
    const VERSION = '20160705000000';

    private $root, $migrationFile;

    public function testCommandCreatesNewMigrationsFileWithAVersionFromConfiguration()
    {
        $this->config->expects($this->once())
            ->method('generateVersionNumber')
            ->willReturn(self::VERSION);

        list($tester, $statusCode) = $this->executeCommand([]);

        $this->assertSame(0, $statusCode);
        $this->assertContains($this->migrationFile, $tester->getDisplay());
        $this->assertTrue($this->root->hasChild($this->migrationFile));
        $this->assertContains('class Version'.self::VERSION, $this->root->getChild($this->migrationFile)->getContent());
    }

    protected function setUp()
    {
        parent::setUp();

        $this->migrationFile = sprintf('Version%s.php', self::VERSION);
        $this->root = vfsStream::setup('migrations');
        $this->config->method('getMigrationsDirectory')
            ->willReturn(vfsStream::url('migrations'));
    }

    protected function createCommand()
    {
        return new GenerateCommand();
    }
}
