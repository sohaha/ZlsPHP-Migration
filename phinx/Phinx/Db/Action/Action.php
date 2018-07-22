<?php

namespace Phinx\Db\Action;

use Phinx\Db\Table\Table;

abstract class Action
{

    /**
     * @var \Phinx\Db\Table\Table
     */
    protected $table;

    /**
     * Constructor
     * @param Table $table the Table to apply the action to
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    /**
     * The table this action will be applied to
     * @return \Phinx\Db\Table\Table
     */
    public function getTable()
    {
        return $this->table;
    }
}