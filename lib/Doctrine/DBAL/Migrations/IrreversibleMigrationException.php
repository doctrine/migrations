<?php

namespace Doctrine\DBAL\Migrations;

/**
 * Exception to be thrown in the down() methods of migrations that signifies it
 * is an irreversible migration and stops execution.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class IrreversibleMigrationException extends \Exception
{
}
