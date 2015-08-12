<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
*/

namespace Doctrine\DBAL\Migrations;

use \Doctrine\DBAL\Migrations\Finder\MigrationFinderInterface;

/**
 * Class for Migrations specific exceptions
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class MigrationException extends \Exception
{
    public static function migrationsNamespaceRequired()
    {
        return new self('Migrations namespace must be configured in order to use Doctrine migrations.', 2);
    }

    public static function migrationsDirectoryRequired()
    {
        return new self('Migrations directory must be configured in order to use Doctrine migrations.', 3);
    }

    public static function noMigrationsToExecute()
    {
        return new self('Could not find any migrations to execute.', 4);
    }

    public static function unknownMigrationVersion($version)
    {
        return new self(sprintf('Could not find migration version %s', $version), 5);
    }

    public static function alreadyAtVersion($version)
    {
        return new self(sprintf('Database is already at version %s', $version), 6);
    }

    public static function duplicateMigrationVersion($version, $class)
    {
        return new self(sprintf('Migration version %s already registered with class %s', $version, $class), 7);
    }

    public static function configurationFileAlreadyLoaded()
    {
        return new self(sprintf('Migrations configuration file already loaded'), 8);
    }

    public static function configurationIncompatibleWithFinder(
        $configurationParameterName,
        MigrationFinderInterface $finder
    ) {
        return new self(
            sprintf(
                'Configuration-parameter "%s" cannot be used with finder of type "%s"',
                $configurationParameterName,
                get_class($finder)
            ),
            9
        );
    }

    public static function configurationNotValid($msg)
    {
        return new self($msg, 10);
    }
}
