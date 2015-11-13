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

use Doctrine\DBAL\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;

class RecursiveRegexFinderTest extends MigrationTestCase
{
    private $finder;

    /**
     * @expectedException InvalidArgumentException
     */
    public function testVersionNameCausesErrorWhen0()
    {
        $this->finder->findMigrations(__DIR__.'/_regression/NoVersionNamed0');
    }

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

        $this->assertCount(6, $migrations);

        $tests = [
            '20150502000000' => 'TestMigrations\\Version20150502000000',
            '20150502000001' => 'TestMigrations\\Version20150502000001',
            '20150502000003' => 'TestMigrations\\Version20150502000003',
            '20150502000004' => 'TestMigrations\\Version20150502000004',
            '20150502000005' => 'TestMigrations\\Version20150502000005',
            '1_reset_versions' => 'TestMigrations\\Version1_reset_versions',
        ];
        foreach($tests as $version => $namespace) {
            $this->assertArrayHasKey($version, $migrations);
            $this->assertEquals($namespace, $migrations[$version]);
        }
        $migrationsForTestSort = (array)$migrations;

        asort($migrationsForTestSort);

        $this->assertTrue($migrationsForTestSort === $migrations,"Finder have to return sorted list of the files.");
        $this->assertArrayNotHasKey('InvalidVersion20150502000002', $migrations);
        $this->assertArrayNotHasKey('Version20150502000002', $migrations);
        $this->assertArrayNotHasKey('20150502000002', $migrations);
        $this->assertArrayNotHasKey('ADeeperRandomClass', $migrations);
        $this->assertArrayNotHasKey('AnotherRandomClassNotStartingWithVersion', $migrations);
        $this->assertArrayNotHasKey('ARandomClass', $migrations);
    }

    protected function setUp()
    {
        $this->finder = new RecursiveRegexFinder();
    }
}
