<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Exception;

use LogicException;

final class XmlNotValid extends LogicException implements ConfigurationException
{
    public static function new() : self
    {
        return new self('XML configuration did not pass the validation test.', 10);
    }
}
