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

namespace Doctrine\DBAL\Migrations\Tests\Finder;

use Doctrine\DBAL\Migrations\Finder\GlobFinder;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;

class GlobFinderTest extends MigrationTestCase
{
    private $finder;

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBadFilenameCausesErrorWhenFindingMigrations()
    {
        $this->finder->findMigrations(__DIR__.'/does/not/exist/at/all');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNonDirectoryCausesErrorWhenFindingMigrations()
    {
        $this->finder->findMigrations(__FILE__);
    }

    public function testFindMigrationsReturnsTheExpectedFilesFromDirectory()
    {
        $migrations = $this->finder->findMigrations(__DIR__.'/_files', 'TestMigrations');

        $this->assertArrayHasKey('20150502000000', $migrations);
        $this->assertEquals('TestMigrations\\Version20150502000000', $migrations['20150502000000']);
        $this->assertArrayHasKey('20150502000001', $migrations);
        $this->assertEquals('TestMigrations\\Version20150502000001', $migrations['20150502000001']);
    }

    protected function setUp()
    {
        $this->finder = new GlobFinder();
    }
}
