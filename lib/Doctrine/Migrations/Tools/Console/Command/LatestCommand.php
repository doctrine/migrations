<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LatestCommand extends AbstractCommand
{
    protected function configure() : void
    {
        $this
            ->setName('migrations:latest')
            ->setDescription('Outputs the latest version number')
        ;

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output) : void
    {
        $configuration = $this->getMigrationConfiguration($input, $output);

        $output->writeln($configuration->getLatestVersion());
    }
}
