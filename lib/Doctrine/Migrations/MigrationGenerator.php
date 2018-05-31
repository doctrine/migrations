<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Tools\Console\Helper\MigrationDirectoryHelper;
use InvalidArgumentException;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_file;
use function is_readable;
use function preg_replace;
use function sprintf;
use function str_replace;
use function trim;

class MigrationGenerator
{
    private const MIGRATION_TEMPLATE = <<<'TEMPLATE'
<?php

declare(strict_types=1);

namespace <namespace>;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version<version> extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
<up>
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
<down>
    }
}

TEMPLATE;

    /** @var Configuration */
    private $configuration;

    /** @var string|null */
    private $template;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function generateMigration(
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
            $this->configuration->getMigrationsNamespace(),
            $version,
            $up !== null ? '        ' . implode("\n        ", explode("\n", $up)) : null,
            $down !== null ? '        ' . implode("\n        ", explode("\n", $down)) : null,
        ];

        $code = str_replace($placeHolders, $replacements, $this->getTemplate());
        $code = preg_replace('/^ +$/m', '', $code);

        $directoryHelper = new MigrationDirectoryHelper($this->configuration);
        $dir             = $directoryHelper->getMigrationDirectory();
        $path            = $dir . '/Version' . $version . '.php';

        file_put_contents($path, $code);

        return $path;
    }

    private function getTemplate() : string
    {
        if ($this->template === null) {
            $this->template = $this->loadCustomTemplate();

            if ($this->template === null) {
                $this->template = self::MIGRATION_TEMPLATE;
            }
        }

        return $this->template;
    }

    private function loadCustomTemplate() : ?string
    {
        $customTemplate = $this->configuration->getCustomTemplate();

        if ($customTemplate === null) {
            return null;
        }

        if (! is_file($customTemplate) || ! is_readable($customTemplate)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The specified template "%s" cannot be found or is not readable.',
                    $customTemplate
                )
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

        if (trim($content) === '') {
            throw new InvalidArgumentException(
                sprintf(
                    'The specified template "%s" is empty.',
                    $customTemplate
                )
            );
        }

        return $content;
    }
}
