<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand;
use InvalidArgumentException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use function sprintf;

class GenerateCommandTest extends CommandTestCase
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

    protected function setUp() : void
    {
        parent::setUp();

        $this->migrationFile = sprintf('Version%s.php', self::VERSION);
        $this->root          = vfsStream::setup('migrations');

        $this->config->method('getMigrationsDirectory')
            ->willReturn(vfsStream::url('migrations'));
    }

    public function testCommandCreatesNewMigrationsFileWithAVersionFromConfiguration() : void
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
    public function testCommandCreatesNewMigrationsFileWithACustomTemplateFromConfiguration(string $templateName) : void
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
        self::assertContains('public function customTemplate()', $this->root->getChild($this->migrationFile)->getContent());
    }

    public function testExceptionShouldBeRaisedWhenCustomTemplateDoesNotExist() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/The specified template ".*" cannot be found or is not readable\./');

        $this->config->method('generateVersionNumber')
                     ->willReturn(self::VERSION);

        $this->config->method('getCustomTemplate')
                     ->willReturn(self::CUSTOM_RELATIVE_TEMPLATE_NAME . '-test');

        $this->executeCommand([]);
    }

    protected function createCommand() : AbstractCommand
    {
        return new GenerateCommand();
    }
}
