<?php

namespace Phinx\Db\Action;

use Phinx\Db\Table\Column;
use Phinx\Db\Table\Table;

class RemoveColumn extends Action
{

    /**
     * The column to be removed
     * @var Column
     */
    protected $column;

    /**
     * Constructor
     * @param Table  $table  The table where the column is
     * @param Column $column The column to be removed
     */
    public function __construct(Table $table, Column $column)
    {
        parent::__construct($table);
        $this->column = $column;
    }

    /**
     * Creates a new RemoveColumn object after assembling the
     * passed arguments.
     * @param Table $table      The table where the column is
     * @param mixed $columnName The name of the column to drop
     * @return RemoveColumn
     */
    public static function build(Table $table, $columnName)
    {
        $column = new Column();
        $column->setName($columnName);

        return new static($table, $column);
    }

    /**
     * Returns the column to be dropped
     * @return Column
     */
    public function getColumn()
    {
        return $this->column;
    }
}
