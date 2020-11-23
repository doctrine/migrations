<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class SyncMetadataCommand extends DoctrineCommand
{
    /** @var string */
    protected static $defaultName = 'migrations:sync-metadata-storage';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setAliases(['sync-metadata-storage'])
            ->setDescription('Ensures that the metadata storage is at the latest version.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command updates metadata storage the latest version.

    <info>%command.full_name%</info>
EOT
            );
    }

    public function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $this->getDependencyFactory()->getMetadataStorage()->ensureInitialized();

        $this->io->success('Metadata storage synchronized');

        return 0;
    }
}
