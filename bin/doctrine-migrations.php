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

$autoloadFiles = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php'
];

$autoloader = false;
foreach ($autoloadFiles as $autoloadFile) {
    if (file_exists($autoloadFile)) {
        require_once $autoloadFile;
        $autoloader = true;
    }
}

if (!$autoloader) {
    if (extension_loaded('phar') && ($uri = Phar::running())) {
        echo 'The phar has been built without dependencies' . PHP_EOL;
    }
    die('vendor/autoload.php could not be found. Did you run `php composer.phar install`?');
}

// Support for using the Doctrine ORM convention of providing a `cli-config.php` file.
$directories = [getcwd(), getcwd() . DIRECTORY_SEPARATOR . 'config'];

$configFile = null;
foreach ($directories as $directory) {
    $configFile = $directory . DIRECTORY_SEPARATOR . 'cli-config.php';

    if (file_exists($configFile)) {
        break;
    }
}

$helperSet = null;
if (file_exists($configFile)) {
    if ( ! is_readable($configFile)) {
        trigger_error(
            'Configuration file [' . $configFile . '] does not have read permission.', E_USER_ERROR
        );
    }

    $helperSet = require $configFile;

    if ( ! ($helperSet instanceof \Symfony\Component\Console\Helper\HelperSet)) {
        foreach ($GLOBALS as $helperSetCandidate) {
            if ($helperSetCandidate instanceof \Symfony\Component\Console\Helper\HelperSet) {
                $helperSet = $helperSetCandidate;
                break;
            }
        }
    }
}

$helperSet = ($helperSet) ?: new \Symfony\Component\Console\Helper\HelperSet();

if(class_exists('\Symfony\Component\Console\Helper\QuestionHelper')) {
    $helperSet->set(new \Symfony\Component\Console\Helper\QuestionHelper(), 'question');
} else {
    $helperSet->set(new \Symfony\Component\Console\Helper\DialogHelper(), 'dialog');
}


$input = file_exists('migrations-input.php')
       ? include 'migrations-input.php' : null;

$output = file_exists('migrations-output.php')
        ? include 'migrations-output.php' : null;

$cli = \Doctrine\DBAL\Migrations\Tools\Console\ConsoleRunner::createApplication($helperSet);
$cli->run($input, $output);

