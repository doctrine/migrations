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

use IteratorAggregate;
use ArrayIterator;
use Countable;

class MigrationSet implements IteratorAggregate, Countable
{
    private $migrations = array();

    public function __construct(array $migrations = array())
    {
        foreach ($migrations as $migration) {
            $this->add($migration);
        }
    }

    public function add(MigrationInfo $migration)
    {
        if ($this->contains($migration)) {
            throw new \RuntimeException(sprintf(
                "There is already a migration with version '%s' in this set.",
                (string)$migration->getVersion()
            ));
        }

        $this->migrations[(string)$migration->getVersion()] = $migration;
    }

    public function getIterator()
    {
        $migrations = array_values($this->migrations);
        usort($migrations, function ($a, $b) {
            return version_compare(
                (string)$a->getVersion(),
                (string)$b->getversion()
            );
        });
        return new ArrayIterator($migrations);
    }

    /**
     * @return mixed
     */
    public function map($fn)
    {
        return array_map($fn, $this->migrations);
    }

    /**
     * @param callack $fn
     * @return MigrationSet
     */
    public function filter($fn)
    {
        return new MigrationSet(array_filter($this->migrations, $fn));
    }

    public function contains(MigrationInfo $migration)
    {
        return isset($this->migrations[(string)$migration->getVersion()]);
    }

    public function count()
    {
        return count($this->migrations);
    }
}
