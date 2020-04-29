<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Tools\Console\Command\SyncMetadataCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SyncMetadataCommandTest extends TestCase
{
    /** @var DependencyFactory|MockObject */
    private $dependencyFactory;

    /** @var MetadataStorage|MockObject */
    private $storage;

    /** @var SyncMetadataCommand */
    private $storageCommand;

    /** @var CommandTester */
    private $storageCommandTester;

    public function testExecute() : void
    {
        $this->storage->expects(self::once())
            ->method('ensureInitialized');

        $this->storageCommandTester->execute([]);

        $output = $this->storageCommandTester->getDisplay(true);

        self::assertStringContainsString('[OK] Metadata storage synchronized', $output);
    }

    protected function setUp() : void
    {
        $this->storage           = $this->createMock(MetadataStorage::class);
        $this->dependencyFactory = $this->createMock(DependencyFactory::class);

        $this->dependencyFactory
            ->expects(self::once())
            ->method('getMetadataStorage')
            ->willReturn($this->storage);

        $this->storageCommand = new SyncMetadataCommand($this->dependencyFactory);

        $this->storageCommandTester = new CommandTester($this->storageCommand);
    }
}
