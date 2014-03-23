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

class Configuration
{
    /**
     * @var bool
     */
    private $outOfOrderMigrationsAllowed = false;

    /**
     * @var bool
     */
    private $validateOnMigrate = false;

    /**
     * @var bool
     */
    private $allowInitOnMigrate = false;

    /**
     * @var string
     */
    private $scriptDirectory;

    public function setOutOfOrderMigrationsAllowed($flag)
    {
        $this->outOfOrderMigrationsAllowed = (bool)$flag;
    }

    public function setValidateOnMigrate($flag)
    {
        $this->validateOnMigrate = (bool)$flag;
    }

    public function setAllowInitOnMigrate($flag)
    {
        $this->allowInitOnMigrate = (bool)$flag;
    }

    public function outOfOrderMigrationsAllowed()
    {
        return $this->outOfOrderMigrationsAllowed;
    }

    public function validateOnMigrate()
    {
        return $this->validateOnMigrate;
    }

    public function allowInitOnMigrate()
    {
        return $this->allowInitOnMigrate;
    }

    public function setScriptDirectory($path)
    {
        $this->scriptDirectory = $path;
    }

    public function getScriptDirectory()
    {
        return $this->scriptDirectory;
    }
}
