<?php

namespace Phinx\Db\Action;

use Phinx\Db\Table\Table;

class UpdateTable extends Action
{
    protected $options = [];

    public function __construct(Table $table, $options)
    {
        parent::__construct($table);
        $this->options = $options;
    }

    public function getOptions()
    {
        return $this->options;
    }
}
