<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Version\Fixture;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class VersionExecutorTestMigration extends AbstractMigration
{
    public bool $preUpExecuted = false;

    public bool $preDownExecuted = false;

    public bool $postUpExecuted = false;

    public bool $postDownExecuted = false;

    private string $description = '';

    public bool $skip  = false;
    public bool $error = false;

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function preUp(Schema $fromSchema): void
    {
        $this->preUpExecuted = true;
        parent::preUp($fromSchema);
    }

    public function up(Schema $schema): void
    {
        $this->skipIf($this->skip);
        $this->abortIf($this->error);

        $this->addSql('SELECT 1', [1], [3]);
        $this->addSql('SELECT 2');
    }

    public function postUp(Schema $toSchema): void
    {
        $this->postUpExecuted = true;
        parent::postUp($toSchema);
    }

    public function preDown(Schema $fromSchema): void
    {
        $this->preDownExecuted = true;
        parent::preDown($fromSchema);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SELECT 3', [5], [7]);
        $this->addSql('SELECT 4', [6], [8]);
    }

    public function postDown(Schema $toSchema): void
    {
        $this->postDownExecuted = true;
        parent::postDown($toSchema);
    }
}
