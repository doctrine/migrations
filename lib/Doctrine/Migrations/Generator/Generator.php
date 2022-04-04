<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Generator;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Generator\Exception\InvalidTemplateSpecified;
use Doctrine\Migrations\Tools\Console\Helper\MigrationDirectoryHelper;
use InvalidArgumentException;

use function explode;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_file;
use function is_readable;
use function preg_match;
use function preg_replace;
use function sprintf;
use function strtr;
use function trim;

/**
 * The Generator class is responsible for generating a migration class.
 *
 * @internal
 */
class Generator
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
final class <className> extends AbstractMigration
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
    }<override>
}

TEMPLATE;

    private Configuration $configuration;

    private ?string $template = null;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function generateMigration(
        string $fqcn,
        ?string $up = null,
        ?string $down = null
    ): string {
        $mch = [];
        if (preg_match('~(.*)\\\\([^\\\\]+)~', $fqcn, $mch) === 0) {
            throw new InvalidArgumentException(sprintf('Invalid FQCN'));
        }

        [$fqcn, $namespace, $className] = $mch;

        $dirs = $this->configuration->getMigrationDirectories();
        if (! isset($dirs[$namespace])) {
            throw new InvalidArgumentException(sprintf('Path not defined for the namespace "%s"', $namespace));
        }

        $dir = $dirs[$namespace];

        $replacements = [
            '<namespace>' => $namespace,
            '<className>' => $className,
            '<up>' => $up !== null ? '        ' . implode("\n        ", explode("\n", $up)) : null,
            '<down>' => $down !== null ? '        ' . implode("\n        ", explode("\n", $down)) : null,
            '<override>' => $this->configuration->isTransactional() ? '' : <<<'METHOD'


    public function isTransactional(): bool
    {
        return false;
    }
METHOD
        ,
        ];

        $code = strtr($this->getTemplate(), $replacements);
        $code = preg_replace('/^ +$/m', '', $code);

        $directoryHelper = new MigrationDirectoryHelper();
        $dir             = $directoryHelper->getMigrationDirectory($this->configuration, $dir);
        $path            = $dir . '/' . $className . '.php';

        file_put_contents($path, $code);

        return $path;
    }

    private function getTemplate(): string
    {
        if ($this->template === null) {
            $this->template = $this->loadCustomTemplate();

            if ($this->template === null) {
                $this->template = self::MIGRATION_TEMPLATE;
            }
        }

        return $this->template;
    }

    /**
     * @throws InvalidTemplateSpecified
     */
    private function loadCustomTemplate(): ?string
    {
        $customTemplate = $this->configuration->getCustomTemplate();

        if ($customTemplate === null) {
            return null;
        }

        if (! is_file($customTemplate) || ! is_readable($customTemplate)) {
            throw InvalidTemplateSpecified::notFoundOrNotReadable($customTemplate);
        }

        $content = file_get_contents($customTemplate);

        if ($content === false) {
            throw InvalidTemplateSpecified::notReadable($customTemplate);
        }

        if (trim($content) === '') {
            throw InvalidTemplateSpecified::empty($customTemplate);
        }

        return $content;
    }
}
