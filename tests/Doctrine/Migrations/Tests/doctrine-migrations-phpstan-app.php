<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\ConsoleRunner;

require_once __DIR__ . '/../../../../vendor/autoload.php';

return ConsoleRunner::createApplication([new DiffCommand()]);
