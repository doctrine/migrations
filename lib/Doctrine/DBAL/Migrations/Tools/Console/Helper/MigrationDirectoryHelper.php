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

namespace Doctrine\DBAL\Migrations\Tools\Console\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\OutputWriter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\Helper;

/**
 * Class ConfigurationHelper
 * @package Doctrine\DBAL\Migrations\Tools\Console\Helper
 * @internal
 */
class MigrationDirectoryHelper extends Helper
{

    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(Configuration $configuration = null)
    {
        $this->configuration = $configuration;
    }

    public function getMigrationDirectory()
    {
        $dir = $this->configuration->getMigrationsDirectory();
        $dir = $dir ? $dir : getcwd();
        $dir = rtrim($dir, '/');

        if (!file_exists($dir)) {
            throw new \InvalidArgumentException(sprintf('Migrations directory "%s" does not exist.', $dir));
        }

        if ($this->configuration->areMigrationsOrganizedByYear()) {
            $dir .= $this->appendDir(date('Y'));
        }

        if ($this->configuration->areMigrationsOrganizedByYearAndMonth()) {
            $dir .= $this->appendDir(date('m'));
        }
        $this->createDirIfNotExists($dir);

        return $dir;
    }

    private function appendDir($dir)
    {
        return DIRECTORY_SEPARATOR . $dir;
    }

    private function createDirIfNotExists($dir)
    {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     *
     * @api
     */
    public function getName()
    {
        return 'MigrationDirectory';
    }


}
