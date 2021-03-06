<?php

namespace Phinx\Db\Adapter;

use Phinx\Db\Action\AddColumn;
use Phinx\Db\Action\AddForeignKey;
use Phinx\Db\Action\AddIndex;
use Phinx\Db\Action\ChangeColumn;
use Phinx\Db\Action\DropForeignKey;
use Phinx\Db\Action\DropIndex;
use Phinx\Db\Action\DropTable;
use Phinx\Db\Action\RemoveColumn;
use Phinx\Db\Action\RenameColumn;
use Phinx\Db\Action\RenameTable;
use Phinx\Db\Action\UpdateTable;
use Phinx\Db\Table\Column;
use Phinx\Db\Table\ForeignKey;
use Phinx\Db\Table\Index;
use Phinx\Db\Table\Table;

/**
 * Table prefix/suffix adapter.
 * Used for inserting a prefix or suffix into table names.
 * @author Samuel Fisher <sam@sfisher.co>
 */
class TablePrefixAdapter extends AdapterWrapper implements DirectActionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getAdapterType()
    {
        return 'TablePrefixAdapter';
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable($tableName)
    {
        $adapterTableName = $this->getAdapterTableName($tableName);

        return parent::hasTable($adapterTableName);
    }

    /**
     * Applies the prefix and suffix to the table name.
     * @param string $tableName
     * @return string
     */
    public function getAdapterTableName($tableName)
    {
        return $this->getPrefix() . $tableName . $this->getSuffix();
    }

    /**
     * Gets the table prefix.
     * @return string
     */
    public function getPrefix()
    {
        return (string)$this->getOption('table_prefix');
    }

    /**
     * Gets the table suffix.
     * @return string
     */
    public function getSuffix()
    {
        return (string)$this->getOption('table_suffix');
    }

    /**
     * {@inheritdoc}
     */
    public function createTable(Table $table, array $columns = [], array $indexes = [])
    {
        $adapterTable = new Table(
            $this->getAdapterTableName($table->getName()),
            $table->getOptions()
        );
        parent::createTable($adapterTable, $columns, $indexes);
    }

    /**
     * {@inheritdoc}
     */
    public function renameTable($tableName, $newTableName)
    {
        $adapter = $this->getAdapter();
        if (!$adapter instanceof DirectActionInterface) {
            throw new \BadMethodCallException('The underlying adapter does not implement DirectActionInterface');
        }
        $adapterTableName = $this->getAdapterTableName($tableName);
        $adapterNewTableName = $this->getAdapterTableName($newTableName);
        $adapter->renameTable($adapterTableName, $adapterNewTableName);
    }

    /**
     * {@inheritdoc}
     */
    public function dropTable($tableName)
    {
        $adapter = $this->getAdapter();
        if (!$adapter instanceof DirectActionInterface) {
            throw new \BadMethodCallException('The underlying adapter does not implement DirectActionInterface');
        }
        $adapterTableName = $this->getAdapterTableName($tableName);
        $adapter->dropTable($adapterTableName);
    }

    /**
     * {@inheritdoc}
     */
    public function truncateTable($tableName)
    {
        $adapterTableName = $this->getAdapterTableName($tableName);
        parent::truncateTable($adapterTableName);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns($tableName)
    {
        $adapterTableName = $this->getAdapterTableName($tableName);

        return parent::getColumns($adapterTableName);
    }

    /**
     * {@inheritdoc}
     */
    public function hasColumn($tableName, $columnName)
    {
        $adapterTableName = $this->getAdapterTableName($tableName);

        return parent::hasColumn($adapterTableName, $columnName);
    }

    /**
     * {@inheritdoc}
     */
    public function addColumn(Table $table, Column $column)
    {
        $adapter = $this->getAdapter();
        if (!$adapter instanceof DirectActionInterface) {
            throw new \BadMethodCallException('The underlying adapter does not implement DirectActionInterface');
        }
        $adapterTableName = $this->getAdapterTableName($table->getName());
        $adapterTable = new Table($adapterTableName, $table->getOptions());
        $adapter->addColumn($adapterTable, $column);
    }

    /**
     * {@inheritdoc}
     */
    public function renameColumn($tableName, $columnName, $newColumnName)
    {
        $adapter = $this->getAdapter();
        if (!$adapter instanceof DirectActionInterface) {
            throw new \BadMethodCallException('The underlying adapter does not implement DirectActionInterface');
        }
        $adapterTableName = $this->getAdapterTableName($tableName);
        $adapter->renameColumn($adapterTableName, $columnName, $newColumnName);
    }

    /**
     * {@inheritdoc}
     */
    public function changeColumn($tableName, $columnName, Column $newColumn)
    {
        $adapter = $this->getAdapter();
        if (!$adapter instanceof DirectActionInterface) {
            throw new \BadMethodCallException('The underlying adapter does not implement DirectActionInterface');
        }
        $adapterTableName = $this->getAdapterTableName($tableName);
        $adapter->changeColumn($adapterTableName, $columnName, $newColumn);
    }

    /**
     * {@inheritdoc}
     */
    public function dropColumn($tableName, $columnName)
    {
        $adapter = $this->getAdapter();
        if (!$adapter instanceof DirectActionInterface) {
            throw new \BadMethodCallException('The underlying adapter does not implement DirectActionInterface');
        }
        $adapterTableName = $this->getAdapterTableName($tableName);
        $adapter->dropColumn($adapterTableName, $columnName);
    }

    /**
     * {@inheritdoc}
     */
    public function hasIndex($tableName, $columns)
    {
        $adapterTableName = $this->getAdapterTableName($tableName);

        return parent::hasIndex($adapterTableName, $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function hasIndexByName($tableName, $indexName)
    {
        $adapterTableName = $this->getAdapterTableName($tableName);

        return parent::hasIndexByName($adapterTableName, $indexName);
    }

    /**
     * {@inheritdoc}
     */
    public function addIndex(Table $table, Index $index)
    {
        $adapter = $this->getAdapter();
        if (!$adapter instanceof DirectActionInterface) {
            throw new \BadMethodCallException('The underlying adapter does not implement DirectActionInterface');
        }
        $adapterTable = new Table($table->getName(), $table->getOptions());
        $adapter->addIndex($adapterTable, $index);
    }

    /**
     * {@inheritdoc}
     */
    public function dropIndex($tableName, $columns)
    {
        $adapter = $this->getAdapter();
        if (!$adapter instanceof DirectActionInterface) {
            throw new \BadMethodCallException('The underlying adapter does not implement DirectActionInterface');
        }
        $adapterTableName = $this->getAdapterTableName($tableName);
        $adapter->dropIndex($adapterTableName, $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function dropIndexByName($tableName, $indexName)
    {
        $adapter = $this->getAdapter();
        if (!$adapter instanceof DirectActionInterface) {
            throw new \BadMethodCallException('The underlying adapter does not implement DirectActionInterface');
        }
        $adapterTableName = $this->getAdapterTableName($tableName);
        $adapter->dropIndexByName($adapterTableName, $indexName);
    }

    /**
     * {@inheritdoc}
     */
    public function hasForeignKey($tableName, $columns, $constraint = null)
    {
        $adapterTableName = $this->getAdapterTableName($tableName);

        return parent::hasForeignKey($adapterTableName, $columns, $constraint);
    }

    /**
     * {@inheritdoc}
     */
    public function addForeignKey(Table $table, ForeignKey $foreignKey)
    {
        $adapter = $this->getAdapter();
        if (!$adapter instanceof DirectActionInterface) {
            throw new \BadMethodCallException('The underlying adapter does not implement DirectActionInterface');
        }
        $adapterTableName = $this->getAdapterTableName($table->getName());
        $adapterTable = new Table($adapterTableName, $table->getOptions());
        $adapter->addForeignKey($adapterTable, $foreignKey);
    }

    /**
     * {@inheritdoc}
     */
    public function dropForeignKey($tableName, $columns, $constraint = null)
    {
        $adapter = $this->getAdapter();
        if (!$adapter instanceof DirectActionInterface) {
            throw new \BadMethodCallException('The underlying adapter does not implement DirectActionInterface');
        }
        $adapterTableName = $this->getAdapterTableName($tableName);
        $adapter->dropForeignKey($adapterTableName, $columns, $constraint);
    }

    /**
     * {@inheritdoc}
     */
    public function insert(Table $table, $row)
    {
        $adapterTableName = $this->getAdapterTableName($table->getName());
        $adapterTable = new Table($adapterTableName, $table->getOptions());
        parent::insert($adapterTable, $row);
    }

    /**
     * {@inheritdoc}
     */
    public function bulkinsert(Table $table, $rows)
    {
        $adapterTableName = $this->getAdapterTableName($table->getName());
        $adapterTable = new Table($adapterTableName, $table->getOptions());
        parent::bulkinsert($adapterTable, $rows);
    }

    /**
     * {@inheritdoc}
     */
    public function executeActions(Table $table, array $actions)
    {
        $adapterTableName = $this->getAdapterTableName($table->getName());
        $adapterTable = new Table($adapterTableName, $table->getOptions());
        foreach ($actions as $k => $action) {
            switch (true) {
                case ($action instanceof AddColumn):
                    $actions[$k] = new AddColumn($adapterTable, $action->getColumn());
                    break;
                case ($action instanceof AddIndex):
                    $actions[$k] = new AddIndex($adapterTable, $action->getIndex());
                    break;
                case ($action instanceof AddForeignKey):
                    $foreignKey = clone $action->getForeignKey();
                    $refTable = $foreignKey->getReferencedTable();
                    $refTableName = $this->getAdapterTableName($refTable->getName());
                    $foreignKey->setReferencedTable(new Table($refTableName, $refTable->getOptions()));
                    $actions[$k] = new AddForeignKey($adapterTable, $foreignKey);
                    break;
                case ($action instanceof ChangeColumn):
                    $actions[$k] = new ChangeColumn($adapterTable, $action->getColumnName(), $action->getColumn());
                    break;
                case ($action instanceof DropForeignKey):
                    $actions[$k] = new DropForeignKey($adapterTable, $action->getForeignKey());
                    break;
                case ($action instanceof DropIndex):
                    $actions[$k] = new DropIndex($adapterTable, $action->getIndex());
                    break;
                case ($action instanceof DropTable):
                    $actions[$k] = new DropTable($adapterTable);
                    break;
                case ($action instanceof RemoveColumn):
                    $actions[$k] = new RemoveColumn($adapterTable, $action->getColumn());
                    break;
                case ($action instanceof RenameColumn):
                    $actions[$k] = new RenameColumn($adapterTable, $action->getColumn(), $action->getNewName());
                    break;
                case ($action instanceof RenameTable):
                    $actions[$k] = new RenameTable($adapterTable, $this->getAdapterTableName($action->getNewName()));
                    break;
                case ($action instanceof UpdateTable):
                    $actions[$k] = new UpdateTable($adapterTable, $action->getOptions());
                    break;
                default:
                    throw new \InvalidArgumentException(
                        sprintf("Forgot to implement table prefixing for action: '%s'", get_class($action))
                    );
            }
        }
        parent::executeActions($adapterTable, $actions);
    }
}
