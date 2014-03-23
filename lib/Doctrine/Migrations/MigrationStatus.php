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

class MigrationStatus
{
    private $executedMigrations = array();
    private $foundMigrations = array();
    private $metadataInitialized = false;

    /**
     * @param \Doctrine\Migrations\MigrationCollection $executedMigrations
     * @param \Doctrine\Migrations\MigrationCollection $foundMigrations
     * @param bool $metadataInitialized
     */
    public function __construct(
        MigrationCollection $executedMigrations,
        MigrationCollection $foundMigrations,
        $metadataInitialized)
    {
        $this->executedMigrations = $executedMigrations;
        $this->foundMigrations = $foundMigrations;
        $this->metadataInitialized = $metadataInitialized;
    }

    public function needsRepair()
    {
        foreach ($this->executedMigrations as $migrationInfo) {
            if ( ! $migrationInfo->wasSuccessfullyExecuted()) {
                return true;
            }
        }

        return false;
    }

    public function containsOutOfOrderMigrations()
    {
        return false;
    }

    public function areChecksumsValid()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isInitialized()
    {
        return $this->metadataInitialized;
    }

    /**
     * @return \Doctrine\Migrations\MigrationCollection
     */
    public function getExecutedMigrations()
    {
        return $this->executedMigrations;
    }

    public function getOutstandingMigrations()
    {
        return new MigrationCollection();
    }

    /**
     * @return int
     */
    public function getMaxInstalledRank()
    {
        return max(
            $this->executedMigrations->map(
                function ($migration) {
                    return $migration->installedRank;
                }
            )
        );
    }
}
