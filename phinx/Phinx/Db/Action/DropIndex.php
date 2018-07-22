<?php

namespace Phinx\Db\Action;

use Phinx\Db\Table\Index;
use Phinx\Db\Table\Table;

class DropIndex extends Action
{

    /**
     * The index to drop
     * @var Index
     */
    protected $index;

    /**
     * Constructor
     * @param Table $table The table owning the index
     * @param Index $index The index to be dropped
     */
    public function __construct(Table $table, Index $index)
    {
        parent::__construct($table);
        $this->index = $index;
    }

    /**
     * Creates a new DropIndex object after assembling the passed
     * arguments.
     * @param Table $table   The table where the index is
     * @param array $columns the indexed columns
     * @return DropIndex
     */
    public static function build(Table $table, array $columns = [])
    {
        $index = new Index();
        $index->setColumns($columns);

        return new static($table, $index);
    }

    /**
     * Creates a new DropIndex when the name of the index to drop
     * is known.
     * @param Table $table The table where the index is
     * @param mixed $name  The name of the index
     * @return DropIndex
     */
    public static function buildFromName(Table $table, $name)
    {
        $index = new Index();
        $index->setName($name);

        return new static($table, $index);
    }

    /**
     * Returns the index to be dropped
     * @return Index
     */
    public function getIndex()
    {
        return $this->index;
    }
}
