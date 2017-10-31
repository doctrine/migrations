<?php

namespace Doctrine\DBAL\Migrations\Tools\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Outputs the latest version number.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class LatestCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('migrations:latest')
            ->setDescription('Outputs the latest version number')
        ;

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $configuration = $this->getMigrationConfiguration($input, $output);

        $output->writeln($configuration->getLatestVersion());
    }
}
