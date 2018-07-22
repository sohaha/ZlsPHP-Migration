<?php

namespace Phinx\Db\Plan;

use Phinx\Db\Table\Column;
use Phinx\Db\Table\Index;
use Phinx\Db\Table\Table;

/**
 * Represents the collection of actions for creating a new table
 */
class NewTable
{
    /**
     * The table to create
     * @var \Phinx\Db\Table\Table
     */
    protected $table;

    /**
     * The list of columns to add
     * @var \Phinx\Db\Table\Column[]
     */
    protected $columns = [];

    /**
     * The list of indexes to create
     * @var \Phinx\Db\Table\Index[]
     */
    protected $indexes = [];

    /**
     * Constructor
     * @param Table $table The table to create
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    /**
     * Adds a column to the collection
     * @param Column $column The column description
     * @return void
     */
    public function addColumn(Column $column)
    {
        $this->columns[] = $column;
    }

    /**
     * Adds an index to the collection
     * @param Index $index The index description
     * @return void
     */
    public function addIndex(Index $index)
    {
        $this->indexes[] = $index;
    }

    /**
     * Returns the table object associated to this collection
     * @return Table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Returns the columns collection
     * @return Column[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Returns the indexes collection
     * @return Index[]
     */
    public function getIndexes()
    {
        return $this->indexes;
    }
}
