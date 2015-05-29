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

namespace Doctrine\DBAL\Migrations\Finder;

/**
 * A MigrationFinder implementation that uses a RegexIterator along with a
 * RecursiveDirectoryIterator.
 *
 * @since   1.0.0-alpha3
 */
final class RecursiveRegexFinder extends AbstractFinder
{
    const PATTERN = '#^.+Version(.{1,255})\.php$#i';

    /**
     * {@inheritdoc}
     */
    public function findMigrations($directory, $namespace=null)
    {
        $dir = $this->getRealPath($directory);

        $migrations = array();
        foreach ($this->createIterator($dir) as $file) {
            list($fileName, $version) = $file;
            static::requireOnce($fileName);
            $className = basename($fileName, '.php');
            $migrations[$version] = sprintf('%s\\%s', $namespace, $className);
        }

        return $migrations;
    }

    private function createIterator($dir)
    {
        return new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            ),
            self::PATTERN,
            \RegexIterator::GET_MATCH
        );
    }
}
