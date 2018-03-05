<?php

namespace Doctrine\DBAL\Migrations;

/**
 * Exception to be thrown in the down() methods of migrations that signifies it
 * is an irreversible migration and stops execution.
 */
class IrreversibleMigrationException extends \Exception
{
}
