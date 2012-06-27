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
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Generates migrations using PHP-native DBAL methods
 *
 * @author Tyler Sommer <sommertm@gmail.com>
 */
class PhpGenerator implements GeneratorInterface
{
    /**
     * @var Doctrine\DBAL\Migrations\Configuration\Configuration
     */
    protected $_configuration;

    /**
     * @var \ReflectionProperty A Reflection on Doctrine\DBAL\Schema\AbstractAsset#$_quoted
     */
    protected $_assetQuotedProperty;

    /**
     * @var \ReflectionProperty A Reflection on Doctrine\DBAL\Schema\ForeignKeyConstraint#$_options
     */
    protected $_foreignKeyOptionsProperty;

    /**
     * Constructor
     *
     * @param Doctrine\DBAL\Migrations\Configuration\Configuration A Migration configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->_configuration = $configuration;

        $reflected = new \ReflectionClass('Doctrine\DBAL\Schema\AbstractAsset');
        $this->_assetQuotedProperty = $reflected->getProperty('_quoted');
        $this->_assetQuotedProperty->setAccessible(true);

        $reflected = new \ReflectionClass('Doctrine\DBAL\Schema\ForeignKeyConstraint');
        $this->_foreignKeyOptionsProperty = $reflected->getProperty('_options');
        $this->_foreignKeyOptionsProperty->setAccessible(true);
    }

    /**
     * Generates a migration using a SchemaDiff
     *
     * @param Schema $fromSchema
     * @param Schema $toSchema
     *
     * @return string Raw PHP code to be used as the body of a Migration
     */
    public function generateMigration(Schema $fromSchema, Schema $toSchema)
    {
        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($fromSchema, $toSchema);

        $code = array('');

        foreach ($schemaDiff->changedSequences as $sequence) {
            $code[] = sprintf('$sequence = $schema->getSequence(\'%s\');', $this->_getQuotedIdentifier($sequence));
            $code[] = sprintf('$sequence->setAllocationSize(%s);', $sequence->getAllocationSize());
            $code[] = sprintf('$sequence->setInitialValue(%s);', $sequence->getInitialValue());
            $code[] = '';
        }

        foreach ($schemaDiff->removedSequences as $sequence) {
            $code[] = sprintf('$schema->dropSequence(\'%s\');', $this->_getQuotedIdentifier($sequence));
            $code[] = '';
        }

        foreach ($schemaDiff->newSequences as $sequence) {
            $code[] = sprintf('$schema->createSequence(\'%s\', %s, %s);', $this->_getQuotedIdentifier($sequence), $sequence->getAllocationSize(), $sequence->getInitialValue());
            $code[] = '';
        }

        foreach ($schemaDiff->newTables as $table) {
            if ($table->getName() == $this->_configuration->getMigrationsTableName()) {
                continue;
            }

            $code[] = '// Create table: ' . $table->getName();
            $code[] = sprintf('$table = $schema->createTable(\'%s\');', $this->_getQuotedIdentifier($table));

            foreach ($table->getOptions() as $name => $value) {
                $code[] = sprintf('$table->addOption(\'%s\', \'%s\');');
            }

            foreach ($table->getColumns() as $column) {
                $code[] = $this->_getCreateColumnCode($column);
            }

            foreach ($table->getIndexes() as $index) {
                $code[] = $this->_getCreateIndexCode($index);
            }

            foreach ($table->getForeignKeys() as $foreignKey) {
                $code[] = $this->_getCreateForeignKeyCode($foreignKey);
            }

            $code[] = '';
        }

        foreach ($schemaDiff->removedTables as $table) {
            if ($table->getName() == $this->_configuration->getMigrationsTableName()) {
                continue;
            }

            $code[] = '// Drop table: ' . $table->getName();
            $code[] = sprintf('$schema->dropTable(\'%s\');', $this->_getQuotedIdentifier($table));
            $code[] = '';
        }

        foreach ($schemaDiff->changedTables as $tableDiff) {
            if ($tableDiff->name == $this->_configuration->getMigrationsTableName()) {
                continue;
            }

            $code[] = '// Alter table: ' . $tableDiff->name;
            $code[] = sprintf('$table = $schema->getTable(\'%s\');', $tableDiff->name);

            if ($tableDiff->newName !== false) {
                $code[] = sprintf('$table->setName(\'%s\');', $tableDiff->newName);
            }

            foreach ($tableDiff->addedColumns as $columnName => $column) {
                $code[] = $this->_getCreateColumnCode($column);
            }

            foreach ($tableDiff->changedColumns as $oldName => $columnDiff) {
                $code[] = sprintf('$column = $table->getColumn(\'%s\');', $oldName);
                foreach ($columnDiff->changedProperties as $property) {
                    $getter = "get{$property}";
                    $value = call_user_func(array($columnDiff->column, $getter));

                    if ($value instanceof Type) {
                        $code[] = sprintf('$column->setType(\Doctrine\DBAL\Types\Type::getType(\'%s\'));', $value->getName());
                    }
                    else {
                        $code[] = sprintf('$column->set%s(%s);', ucfirst($property), $this->_exportVar($value));
                    }
                }
            }

            foreach ($tableDiff->removedColumns as $columnName => $removed) {
                $code[] = sprintf('$table->dropColumn(\'%s\');', $columnName);
            }

            foreach ($tableDiff->renamedColumns as $oldName => $column) {
                $code[] = sprintf('$column = $table->getColumn(\'%s\');', $oldName);
                $code[] = sprintf('$column->setName(\'%s\');', $column->getName());
            }

            foreach ($tableDiff->addedIndexes as $indexName => $index) {
                $code[] = $this->_getCreateIndexCode($index);
            }

            foreach ($tableDiff->changedIndexes as $oldName => $index) {
                $code[] = $this->_getDropIndexCode($oldName);
                $code[] = $this->_getCreateIndexCode($index);
            }

            foreach ($tableDiff->removedIndexes as $indexName => $removed) {
                $code[] = $this->_getDropIndexCode($indexName);
            }

            foreach ($tableDiff->addedForeignKeys as $foreignKey) {
                $code[] = $this->_getCreateForeignKeyCode($foreignKey);
            }

            foreach ($tableDiff->changedForeignKeys as $foreignKey) {
                $code[] = $this->_getDropForeignKeyCode($foreignKey);
                $code[] = $this->_getCreateForeignKeyCode($foreignKey);
            }

            foreach ($tableDiff->removedForeignKeys as $foreignKey) {
                $code[] = $this->_getDropForeignKeyCode($foreignKey);
            }

            $code[] = '';
        }

        return implode("\n", $code);
    }

    /**
     * @param Doctrine\DBAL\Schema\Column $column
     * @return string
     */
    protected function _getCreateColumnCode(Schema\Column $column)
    {
        return sprintf('$table->addColumn(\'%s\', \'%s\', %s);',
            $this->_getQuotedIdentifier($column),
            $column->getType()->getName(),
            $this->_exportVar($this->_getColumnOptions($column))
        );
    }

    /**
     * @param Doctrine\DBAL\Schema\Index $index
     * @return string
     */
    protected function _getCreateIndexCode(Schema\Index $index)
    {
        if ($index->isPrimary()) {
            $str = '$table->setPrimaryKey(%s, \'%s\');';
        }
        else if ($index->isUnique()) {
            $str = '$table->addUniqueIndex(%s, \'%s\');';
        }
        else {
            $str = '$table->addIndex(%s, \'%s\');';
        }

        return sprintf($str, $this->_exportVar($index->getColumns()), $this->_getQuotedIdentifier($index));
    }

    /**
     * @param string $indexName The name of the index to drop
     * @return string
     */
    protected function _getDropIndexCode($indexName)
    {
        return <<<END
\$reflected = new \ReflectionClass('Doctrine\DBAL\Schema\Table');
\$indexesProperty = \$reflected->getProperty('_indexes');
\$indexesProperty->setAccessible(true);
\$indexes = \$indexesProperty->getValue(\$table);
unset(\$indexes['{$indexName}']);
\$indexesProperty->setValue(\$table, \$indexes);
END;
    }

    /**
     * @param Doctrine\DBAL\Schema\ForeignKeyConstraint $foreignKey
     * @return string
     */
    protected function _getCreateForeignKeyCode(Schema\ForeignKeyConstraint $foreignKey)
    {
        return sprintf('$table->addForeignKeyConstraint(\'%s\', %s, %s, %s, \'%s\');',
            $foreignKey->getForeignTableName(),
            $this->_exportVar($foreignKey->getLocalColumns()),
            $this->_exportVar($foreignKey->getForeignColumns()),
            $this->_exportVar($this->_getForeignKeyOptions($foreignKey)),
            $this->_getQuotedIdentifier($foreignKey)
        );
    }

    /**
     * @param string $indexName The name of the index to drop
     * @return string
     */
    protected function _getDropForeignKeyCode(Schema\ForeignKeyConstraint $foreignKey)
    {
        $keyName = strtolower($foreignKey->getName());

        return <<<END
\$reflected = new \ReflectionClass('Doctrine\DBAL\Schema\Table');
\$fkConstraintsProperty = \$reflected->getProperty('_fkConstraints');
\$fkConstraintsProperty->setAccessible(true);
\$fkConstraints = \$fkConstraintsProperty->getValue(\$table);
unset(\$fkConstraints['{$keyName}']);
\$fkConstraintsProperty->setValue(\$table, \$fkConstraints);
END;
    }

    /**
     * @param Doctrine\DBAL\Schema\ForeignKeyConstraint $foreignKey
     * @return array
     */
    protected function _getForeignKeyOptions(Schema\ForeignKeyConstraint $foreignKey)
    {
        return $this->_foreignKeyOptionsProperty->getValue($foreignKey);
    }

    /**
     * @param Doctrine\DBAL\Schema\Column $column
     * @return array
     */
    protected function _getColumnOptions(Schema\Column $column) {
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
     * @param Doctrine\DBAL\Schema\AbstractAsset $asset
     * @return string
     */
    protected function _getQuotedIdentifier(Schema\AbstractAsset $asset)
    {
        return $this->_assetQuotedProperty->getValue($asset) ? '"' . $asset->getName() . '"' : $asset->getName();
    }

    /**
     * @param mixed $var
     * @return string
     */
    protected function _exportVar($var)
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
