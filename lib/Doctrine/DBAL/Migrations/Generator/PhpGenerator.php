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

namespace Doctrine\DBAL\Migrations\Generator;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Generates migrations using PHP-native DBAL methods
 *
 * @author Tyler Sommer <sommertm@gmail.com>
 */
class PhpGenerator implements GeneratorInterface
{
    /**
     * @var \Doctrine\DBAL\Migrations\Configuration\Configuration
     */
    protected $configuration;

    /**
     * Constructor
     *
     * @param \Doctrine\DBAL\Migrations\Configuration\Configuration $configuration A Migration configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Generates a migration using a SchemaDiff
     *
     * @param \Doctrine\DBAL\Schema\Schema $fromSchema
     * @param \Doctrine\DBAL\Schema\Schema $toSchema
     *
     * @throws \RuntimeException if Doctrine DBAL is not version 2.3.0 or later
     * @return string Raw PHP code to be used as the body of a Migration
     */
    public function generateMigration(Schema\Schema $fromSchema, Schema\Schema $toSchema)
    {
        if (\Doctrine\DBAL\Version::compare('2.3.0') < 0) {
            throw new \RuntimeException('The PHP migration generator requires Doctrine DBAL 2.3.0 or later to function.');
        }

        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($fromSchema, $toSchema);

        $code = array('');

        foreach ($schemaDiff->changedSequences as $sequence) {
            $code[] = sprintf('$sequence = $schema->getSequence(\'%s\');', $this->getQuotedIdentifier($sequence));
            $code[] = sprintf('$sequence->setAllocationSize(%s);', $sequence->getAllocationSize());
            $code[] = sprintf('$sequence->setInitialValue(%s);', $sequence->getInitialValue());
            $code[] = '';
        }

        foreach ($schemaDiff->removedSequences as $sequence) {
            $code[] = sprintf('$schema->dropSequence(\'%s\');', $this->getQuotedIdentifier($sequence));
            $code[] = '';
        }

        foreach ($schemaDiff->newSequences as $sequence) {
            $code[] = sprintf('$schema->createSequence(\'%s\', %s, %s);', $this->getQuotedIdentifier($sequence), $sequence->getAllocationSize(), $sequence->getInitialValue());
            $code[] = '';
        }

        foreach ($schemaDiff->newTables as $table) {
            if ($table->getName() == $this->configuration->getMigrationsTableName()) {
                continue;
            }

            $code[] = '// Create table: ' . $table->getName();
            $code[] = sprintf('$table = $schema->createTable(\'%s\');', $this->getQuotedIdentifier($table));

            foreach ($table->getOptions() as $name => $value) {
                $code[] = sprintf('$table->addOption(\'%s\', \'%s\');');
            }

            foreach ($table->getColumns() as $column) {
                $code[] = $this->getCreateColumnCode($column);
            }

            foreach ($table->getIndexes() as $index) {
                $code[] = $this->getCreateIndexCode($index);
            }

            foreach ($table->getForeignKeys() as $foreignKey) {
                $code[] = $this->getCreateForeignKeyCode($foreignKey);
            }

            $code[] = '';
        }

        foreach ($schemaDiff->removedTables as $table) {
            if ($table->getName() == $this->configuration->getMigrationsTableName()) {
                continue;
            }

            $code[] = '// Drop table: ' . $table->getName();
            $code[] = sprintf('$schema->dropTable(\'%s\');', $this->getQuotedIdentifier($table));
            $code[] = '';
        }

        foreach ($schemaDiff->changedTables as $tableDiff) {
            if ($tableDiff->name == $this->configuration->getMigrationsTableName()) {
                continue;
            }

            $code[] = '// Alter table: ' . $tableDiff->name;
            $code[] = sprintf('$table = $schema->getTable(\'%s\');', $tableDiff->name);

            if ($tableDiff->newName !== false) {
                $code[] = sprintf('$table->setName(\'%s\');', $tableDiff->newName);
            }

            foreach ($tableDiff->addedColumns as $columnName => $column) {
                $code[] = $this->getCreateColumnCode($column);
            }

            foreach ($tableDiff->changedColumns as $oldName => $columnDiff) {
                $code[] = sprintf('$column = $table->getColumn(\'%s\');', $oldName);
                foreach ($columnDiff->changedProperties as $property) {
                    $value = call_user_func(array($columnDiff->column, 'get'.$property));

                    if ($value instanceof Type) {
                        $code[] = sprintf('$column->setType(\Doctrine\DBAL\Types\Type::getType(\'%s\'));', $value->getName());
                    }
                    else {
                        $code[] = sprintf('$column->set%s(%s);', ucfirst($property), $this->exportVar($value));
                    }
                }
            }

            foreach ($tableDiff->removedColumns as $columnName => $removed) {
                $code[] = sprintf('$table->dropColumn(\'%s\');', $columnName);
            }

            // TODO Should this be removed?
            foreach ($tableDiff->renamedColumns as $oldName => $column) {
                $code[] = sprintf('$table->renameColumn(\'%s\', \'%s\'', $oldName, $column->getName());
            }

            foreach ($tableDiff->addedIndexes as $indexName => $index) {
                $code[] = $this->getCreateIndexCode($index);
            }

            $droppedIndexes = array();
            foreach ($tableDiff->changedIndexes as $oldName => $index) {
                $droppedIndexes[$oldName] = $index;
            }

            foreach ($tableDiff->removedIndexes as $indexName => $index) {
                $droppedIndexes[$indexName] = $index;
            }

            if (!empty($droppedIndexes)) {
                foreach ($droppedIndexes as $indexName => $index) {
                    $code[] = sprintf('$table->dropIndex(\'%s\');', $indexName);
                }
            }

            foreach ($tableDiff->changedIndexes as $oldName => $index) {
                $code[] = $this->getCreateIndexCode($index);
            }

            foreach ($tableDiff->addedForeignKeys as $foreignKey) {
                $code[] = $this->getCreateForeignKeyCode($foreignKey);
            }

            $droppedForeignKeys = array();
            foreach ($tableDiff->changedForeignKeys as $foreignKey) {
                $droppedForeignKeys[] = $foreignKey;
            }

            foreach ($tableDiff->removedForeignKeys as $foreignKey) {
                $droppedForeignKeys[] = $foreignKey;
            }

            if (!empty($droppedForeignKeys)) {
                foreach ($droppedForeignKeys as $foreignKey) {
                    $code[] = sprintf('$table->removeForeignKey(\'%s\');', $foreignKey->getName());
                }
            }

            foreach ($tableDiff->changedForeignKeys as $foreignKey) {
                $code[] = $this->getCreateForeignKeyCode($foreignKey);
            }

            $code[] = '';
        }

        return implode("\n", $code);
    }

    /**
     * @param \Doctrine\DBAL\Schema\Column $column
     *
     * @return string
     */
    protected function getCreateColumnCode(Schema\Column $column)
    {
        return sprintf('$table->addColumn(\'%s\', \'%s\', %s);',
            $this->getQuotedIdentifier($column),
            $column->getType()->getName(),
            $this->exportVar($this->getColumnOptions($column))
        );
    }

    /**
     * @param \Doctrine\DBAL\Schema\Index $index
     *
     * @return string
     */
    protected function getCreateIndexCode(Schema\Index $index)
    {
        if ($index->isPrimary()) {
            $str = '$table->setPrimaryKey(%s, \'%s\');';
        } elseif ($index->isUnique()) {
            $str = '$table->addUniqueIndex(%s, \'%s\');';
        } else {
            $str = '$table->addIndex(%s, \'%s\');';
        }

        return sprintf($str, $this->exportVar($index->getColumns()), $this->getQuotedIdentifier($index));
    }

    /**
     * @param \Doctrine\DBAL\Schema\ForeignKeyConstraint $foreignKey
     *
     * @return string
     */
    protected function getCreateForeignKeyCode(Schema\ForeignKeyConstraint $foreignKey)
    {
        return sprintf('$table->addForeignKeyConstraint(\'%s\', %s, %s, %s, \'%s\');',
            $foreignKey->getForeignTableName(),
            $this->exportVar($foreignKey->getLocalColumns()),
            $this->exportVar($foreignKey->getForeignColumns()),
            $this->exportVar($this->getForeignKeyOptions($foreignKey)),
            $this->getQuotedIdentifier($foreignKey)
        );
    }

    /**
     * @param \Doctrine\DBAL\Schema\ForeignKeyConstraint $foreignKey
     *
     * @return array
     */
    protected function getForeignKeyOptions(Schema\ForeignKeyConstraint $foreignKey)
    {
        return $foreignKey->getOptions();
    }

    /**
     * @param \Doctrine\DBAL\Schema\Column $column
     *
     * @return array
     */
    protected function getColumnOptions(Schema\Column $column) {
        $options = array();

        if ($column->getLength() !== null) {
            $options['length'] = $column->getLength();
        }

        if ($column->getPrecision() !== 0) {
            $options['precision'] = $column->getPrecision();
        }

        if ($column->getScale() !== 0) {
            $options['scale'] = $column->getScale();
        }

        if ($column->getUnsigned() !== false) {
            $options['unsigned'] = true;
        }

        if ($column->getFixed() !== false) {
            $options['fixed'] = true;
        }

        if ($column->getNotNull() !== true) {
            $options['notNull'] = false;
        }

        if ($column->getDefault() !== null) {
            $options['default'] = $column->getDefault();
        }

        if ($column->getColumnDefinition() !== null) {
            $options['columnDefinition'] = $column->getColumnDefinition();
        }

        if ($column->getPlatformOptions() !== array('version' => false) && $column->getPlatformOptions() !== array()) {
            $options['platformOptions'] = $column->getPlatformOptions();
        }

        if ($column->getAutoincrement() !== false) {
            $options['autoincrement'] = $column->getAutoincrement();
        }

        if ($column->getComment() !== null) {
            $options['comment'] = $column->getComment();
        }

        return $options;
    }

    /**
     * @param \Doctrine\DBAL\Schema\AbstractAsset $asset
     *
     * @return string
     */
    protected function getQuotedIdentifier(Schema\AbstractAsset $asset)
    {
        return $asset->isQuoted() ? '"' . $asset->getName() . '"' : $asset->getName();
    }

    /**
     * @param mixed $var
     *
     * @return string
     */
    protected function exportVar($var)
    {
        $export = var_export($var, true);

        if (is_array($var)) {
            $export = preg_replace('/[0-9+] \=\> /', '', $export);
            $export = str_replace(array("array (\n)", "\n  ", 'array ('), array('array()', "\n    ", 'array('), $export);

            if (count($var) == 1) {
                $export = str_replace(array("\n", '    ', ',)'), array('', '', ')'), $export);
            }
        }

        return $export;
    }
}
