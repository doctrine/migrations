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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Migrations;

class MigrationInfo
{
    /**
     * @var Version
     */
    private $version;

    /**
     * @var int
     */
    public $versionRank;

    /**
     * @var int
     */
    public $installedRank;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $script;

    /**
     * @var string
     */
    public $checksum;

    /**
     * @var string
     */
    public $installedBy;

    /**
     * @var \DateTime
     */
    public $installedOn;

    /**
     * @var int
     */
    public $executionTime;

    /**
     * @var bool
     */
    private $success = false;

    public function __construct(Version $version)
    {
        $this->version = $version;
    }

    /**
     * @return \Doctrine\Migrations\Version
     */
    public function getVersion()
    {
        return $this->version;
    }

    public function wasSuccessfullyExecuted()
    {
        return $this->success;
    }

    public function setSuccess($success)
    {
        $this->success = $success;
    }
}
