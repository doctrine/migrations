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

namespace Doctrine\DBAL\Migrations\Event;

use Doctrine\Common\EventArgs;
use Doctrine\DBAL\Migrations\Configuration\Configuration;

class MigrationsEventArgs extends EventArgs
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * The direction of the migration.
     *
     * @var string (up|down)
     */
    private $direction;

    /**
     * Whether or not the migrations are executing in dry run mode.
     *
     * @var bool
     */
    private $dryRun;

    public function __construct(Configuration $config, $direction, $dryRun)
    {
        $this->config = $config;
        $this->direction = $direction;
        $this->dryRun = (bool) $dryRun;
    }

    public function getConfiguration()
    {
        return $this->config;
    }

    public function getConnection()
    {
        return $this->config->getConnection();
    }

    public function getDirection()
    {
        return $this->direction;
    }

    public function isDryRun()
    {
        return $this->dryRun;
    }
}
