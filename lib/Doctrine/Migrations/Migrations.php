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

/**
 * Facade for all migration operations
 */
class Migrations
{
    /**
     * @var \Doctrine\Migrations\Configuration
     */
    private $configuration;

    /**
     * @var \Doctrine\Migrations\MetadataStorage
     */
    private $metadataStorage;

    public function __construct(Configuration $configuration, MetadataStorage $metadataStorage)
    {
        $this->configuration = $configuration;
        $this->metadataStorage = $metadataStorage;
    }

    /**
     * Get information about all migrations, executed and non executed so far.
     *
     * @return \Doctrine\Migrations\MigrationStatus
     */
    public function getInfo()
    {
        return new MigrationStatus(
            $this->metadataStorage->isInitialized() ? $this->metadataStorage->getExecutedMigrations() : array(),
            new MigrationSet(),
            $this->metadataStorage->isInitialized()
        );
    }

    /**
     * Initialize the migration metadata in the underlying storage.
     *
     * @return void
     */
    public function initMetadata()
    {
        $task = new Task\InitializeMetadata($this->metadataStorage);
        $task->execute($this->getInfo());
    }

    /**
     * Migrate all the missing scripts to the most recent version.
     *
     * @return void
     */
    public function migrate()
    {
        $status = $this->getInfo();

        $task = new Task\Migrate(
            $this->configuration,
            $this->metadataStorage,
            $this->configuration->getExecutorRegistry()
        );
        $task->execute($status);
    }

    /**
     * If a migration has failed you can cleanup the migration storage from the
     * failed run.
     *
     * Your own user objects have to be cleaned up manually.
     */
    public function repair()
    {
        $status = $this->getInfo();

        $task = new Task\Repair($this->metadataStorage);
        $task->execute($status);
    }
}

