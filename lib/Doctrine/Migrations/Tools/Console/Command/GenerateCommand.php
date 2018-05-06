<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Tools\Console\Helper\MigrationDirectoryHelper;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function escapeshellarg;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_file;
use function is_readable;
use function preg_replace;
use function proc_open;
use function sprintf;
use function str_replace;

class GenerateCommand extends AbstractCommand
{
    private const MIGRATION_TEMPLATE = <<<'TEMPLATE'
<?php

declare(strict_types=1);

namespace <namespace>;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

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

TEMPLATE;

    /** @var null|string */
    private $instanceTemplate;

    protected function configure() : void
    {
        $this
            ->setName('migrations:generate')
            ->setDescription('Generate a blank migration class.')
            ->addOption(
                'editor-cmd',
                null,
                InputOption::VALUE_OPTIONAL,
                'Open file with this command upon creation.'
            )
            ->setHelp(<<<EOT
The <info>%command.name%</info> command generates a blank migration class:

    <info>%command.full_name%</info>

You can optionally specify a <comment>--editor-cmd</comment> option to open the generated file in your favorite editor:

    <info>%command.full_name% --editor-cmd=mate</info>
EOT
        );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output) : void
    {
        $configuration = $this->getMigrationConfiguration($input, $output);

        $this->loadCustomTemplate($configuration, $output);

        $version = $configuration->generateVersionNumber();
        $path    = $this->generateMigration($configuration, $input, $version);

        $output->writeln(sprintf('Generated new migration class to "<info>%s</info>"', $path));
    }

    protected function getTemplate() : string
    {
        if ($this->instanceTemplate === null) {
            $this->instanceTemplate = self::MIGRATION_TEMPLATE;
        }

        return $this->instanceTemplate;
    }

    protected function generateMigration(
        Configuration $configuration,
        InputInterface $input,
        string $version,
        ?string $up = null,
        ?string $down = null
    ) : string {
        $placeHolders = [
            '<namespace>',
            '<version>',
            '<up>',
            '<down>',
        ];
        $replacements = [
            $configuration->getMigrationsNamespace(),
            $version,
            $up ? '        ' . implode("\n        ", explode("\n", $up)) : null,
            $down ? '        ' . implode("\n        ", explode("\n", $down)) : null,
        ];

        $code = str_replace($placeHolders, $replacements, $this->getTemplate());
        $code = preg_replace('/^ +$/m', '', $code);

        $directoryHelper = new MigrationDirectoryHelper($configuration);
        $dir             = $directoryHelper->getMigrationDirectory();
        $path            = $dir . '/Version' . $version . '.php';

        file_put_contents($path, $code);

        $editorCmd = $input->getOption('editor-cmd');

        if ($editorCmd) {
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

        if (! is_file($customTemplate) || ! is_readable($customTemplate)) {
            throw new InvalidArgumentException(
                'The specified template "' . $customTemplate . '" cannot be found or is not readable.'
            );
        }

        $content = file_get_contents($customTemplate);

        if ($content === false) {
            throw new InvalidArgumentException(
                sprintf(
                    'The specified template "%s" could not be read.',
                    $customTemplate
                )
            );
        }

        $output->writeln(sprintf('Using custom migration template "<info>%s</info>"', $customTemplate));
        $this->instanceTemplate = $content;
    }
}
