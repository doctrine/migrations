<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Tools\Console\Command\SyncMetadataCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class SyncMetadataCommandTest extends TestCase
{
    /** @var DependencyFactory|MockObject */
    private $dependencyFactory;

    /** @var MetadataStorage|MockObject */
    private $storage;

    /** @var SyncMetadataCommand */
    private $storageCommand;

    public function testExecute() : void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $this->storage->expects(self::once())
            ->method('ensureInitialized');

        $output->expects(self::once())
            ->method('writeln')
            ->with('Metadata storage synchronized');

        $this->storageCommand->execute($input, $output);
    }

    protected function setUp() : void
    {
        $this->storage           = $this->createMock(MetadataStorage::class);
        $this->dependencyFactory = $this->createMock(DependencyFactory::class);

        $this->dependencyFactory
            ->expects(self::once())
            ->method('getMetadataStorage')
            ->willReturn($this->storage);

        $this->storageCommand = new SyncMetadataCommand(null, $this->dependencyFactory);
    }
}
