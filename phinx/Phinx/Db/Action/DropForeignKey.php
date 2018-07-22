<?php

namespace Phinx\Db\Action;

use Phinx\Db\Table\ForeignKey;
use Phinx\Db\Table\Table;

class DropForeignKey extends Action
{

    /**
     * The foreign key to remove
     * @var ForeignKey
     */
    protected $foreignKey;

    /**
     * Constructor
     * @param Table      $table      The table to remove the constraint from
     * @param ForeignKey $foreignKey The foreign key to remove
     */
    public function __construct(Table $table, ForeignKey $foreignKey)
    {
        parent::__construct($table);
        $this->foreignKey = $foreignKey;
    }

    /**
     * Creates a new DropForeignKey object after building the ForeignKey
     * definition out of the passed arguments.
     * @param Table           $table      The table to delete the foreign key from
     * @param string|string[] $columns    The columns participating in the foreign key
     * @param string|null     $constraint The constraint name
     * @return DropForeignKey
     */
    public static function build(Table $table, $columns, $constraint = null)
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        $foreignKey = new ForeignKey();
        $foreignKey->setColumns($columns);
        if ($constraint) {
            $foreignKey->setConstraint($constraint);
        }

        return new static($table, $foreignKey);
    }

    /**
     * Returns the  foreign key to remove
     * @return ForeignKey
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }
}
