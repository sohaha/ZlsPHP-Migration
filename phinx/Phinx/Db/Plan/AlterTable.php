<?php

namespace Phinx\Db\Plan;

use Phinx\Db\Action\Action;
use Phinx\Db\Table\Table;

/**
 * A collection of ALTER actions for a single table
 */
class AlterTable
{
    /**
     * The table
     * @var \Phinx\Db\Table\Table
     */
    protected $table;

    /**
     * The list of actions to execute
     * @var \Phinx\Db\Action\Action[]
     */
    protected $actions = [];

    /**
     * Constructor
     * @param Table $table The table to change
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    /**
     * Adds another action to the collection
     * @param Action $action The action to add
     * @return void
     */
    public function addAction(Action $action)
    {
        $this->actions[] = $action;
    }

    /**
     * Returns the table associated to this collection
     * @return Table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Returns an array with all collected actions
     * @return Action[]
     */
    public function getActions()
    {
        return $this->actions;
    }
}
