<?php

declare(strict_types=1);

use Doctrine\Migrations\Configuration\Configuration;

$c = new Configuration();
$c->setName('inline');

return $c;
