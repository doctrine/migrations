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

namespace Doctrine\DBAL\Migrations\Tests;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Symfony\Component\Console\Output\StreamOutput;

abstract class MigrationTestCase extends \PHPUnit_Framework_TestCase
{
    public function getSqliteConnection()
    {
        $params = array('driver' => 'pdo_sqlite', 'memory' => true);

        return DriverManager::getConnection($params);
    }

    /**
     * @return Configuration
     */
    public function getSqliteConfiguration()
    {
        $config = new Configuration($this->getSqliteConnection());
        $config->setMigrationsDirectory(\sys_get_temp_dir());
        $config->setMigrationsNamespace('DoctrineMigrations');

        return $config;
    }

    public function getOutputStream()
    {
        $stream = fopen('php://memory', 'r+', false);
        $streamOutput = new StreamOutput($stream);

        return $streamOutput;
    }

    public function getOutputStreamContent(StreamOutput $streamOutput)
    {
        $stream = $streamOutput->getStream();
        rewind($stream);

        return stream_get_contents($stream);
    }
}
