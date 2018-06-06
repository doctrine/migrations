<?php


namespace Doctrine\DBAL\Migrations\Tools\Console\Command;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\MigrationDirectoryHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Command for generating new blank migration classes
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class GenerateCommand extends AbstractCommand
{
    private static $_template =
            '<?php declare(strict_types=1);

namespace <namespace>;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version<version> extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
<up>
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
<down>
    }
}
';

    private $instanceTemplate;

    protected function configure()
    {
        $this
                ->setName('migrations:generate')
                ->setDescription('Generate a blank migration class.')
                ->addOption('editor-cmd', null, InputOption::VALUE_OPTIONAL, 'Open file with this command upon creation.')
                ->setHelp(<<<EOT
The <info>%command.name%</info> command generates a blank migration class:

    <info>%command.full_name%</info>

You can optionally specify a <comment>--editor-cmd</comment> option to open the generated file in your favorite editor:

    <info>%command.full_name% --editor-cmd=mate</info>
EOT
        );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $configuration = $this->getMigrationConfiguration($input, $output);

        $this->loadCustomTemplate($configuration, $output);

        $version = $configuration->generateVersionNumber();
        $path    = $this->generateMigration($configuration, $input, $version);

        $output->writeln(sprintf('Generated new migration class to "<info>%s</info>"', $path));
    }

    protected function getTemplate()
    {
        if ($this->instanceTemplate === null) {
            $this->instanceTemplate = self::$_template;
        }

        return $this->instanceTemplate;
    }

    protected function generateMigration(Configuration $configuration, InputInterface $input, $version, $up = null, $down = null)
    {
        $placeHolders = [
            '<namespace>',
            '<version>',
            '<up>',
            '<down>',
        ];
        $replacements = [
            $configuration->getMigrationsNamespace(),
            $version,
            $up ? "        " . implode("\n        ", explode("\n", $up)) : null,
            $down ? "        " . implode("\n        ", explode("\n", $down)) : null,
        ];

        $code = str_replace($placeHolders, $replacements, $this->getTemplate());
        $code = preg_replace('/^ +$/m', '', $code);

        $directoryHelper = new MigrationDirectoryHelper($configuration);
        $dir             = $directoryHelper->getMigrationDirectory();
        $path            = $dir . '/Version' . $version . '.php';

        file_put_contents($path, $code);

        if ($editorCmd = $input->getOption('editor-cmd')) {
            proc_open($editorCmd . ' ' . escapeshellarg($path), [], $pipes);
        }

        return $path;
    }

    protected function loadCustomTemplate(Configuration $configuration, OutputInterface $output) : void
    {
        $customTemplate = $configuration->getCustomTemplate();

        if ($customTemplate === null) {
            return;
        }

        if ( ! is_file($customTemplate) || ! is_readable($customTemplate)) {
            throw new \InvalidArgumentException(
                'The specified template "' . $customTemplate . '" cannot be found or is not readable.'
            );
        }

        $content = file_get_contents($customTemplate);

        if ($content === false) {
            throw new \InvalidArgumentException('The specified template "' . $customTemplate . '" could not be read.');
        }

        $output->writeln(sprintf('Using custom migration template "<info>%s</info>"', $customTemplate));
        $this->instanceTemplate = $content;
    }
}
