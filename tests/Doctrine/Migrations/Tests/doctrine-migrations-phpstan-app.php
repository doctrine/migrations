<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\ConsoleRunner;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;

require_once __DIR__ . '/../../../../vendor/autoload.php';


$helperSet = $helperSet ?? new HelperSet();
$helperSet->set(new QuestionHelper(), 'question');

return ConsoleRunner::createApplication($helperSet, [new DiffCommand()]);
