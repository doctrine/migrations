<?php

declare(strict_types=1);

use Doctrine\Migrations\Configuration\Configuration;

$c = new Configuration();
$c->setCustomTemplate('foo');

return $c;
