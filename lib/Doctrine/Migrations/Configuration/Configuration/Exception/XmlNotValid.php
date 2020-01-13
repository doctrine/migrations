<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Configuration\Exception;

use Doctrine\Migrations\Configuration\Exception\ConfigurationException;
use LogicException;

final class XmlNotValid extends LogicException implements ConfigurationException
{
    public static function malformed() : self
    {
        return new self('The XML configuration is malformed.');
    }

    public static function failedValidation() : self
    {
        return new self('XML configuration did not pass the validation test.', 10);
    }
}
