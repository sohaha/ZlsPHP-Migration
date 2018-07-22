<?php

namespace Phinx\Db\Action;

use Phinx\Db\Table\Column;
use Phinx\Db\Table\Table;

class AddColumn extends Action
{

    /**
     * The column to add
     * @var Column
     */
    protected $column;

    /**
     * Constructor
     * @param Table  $table  The table to add the column to
     * @param Column $column The column to add
     */
    public function __construct(Table $table, Column $column)
    {
        parent::__construct($table);
        $this->column = $column;
    }

    /**
     * Returns a new AddColumn object after assembling the given commands
     * @param Table $table      The table to add the column to
     * @param mixed $columnName The column name
     * @param mixed $type       The column type
     * @param mixed $options    The column options
     * @return AddColumn
     */
    public static function build(Table $table, $columnName, $type = null, $options = [])
    {
        $column = new Column();
        $column->setName($columnName);
        $column->setType($type);
        $column->setOptions($options); // map options to column methods

        return new static($table, $column);
    }

    /**
     * Returns the column to be added
     * @return Column
     */
    public function getColumn()
    {
        return $this->column;
    }
}
