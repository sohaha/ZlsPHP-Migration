<?php

namespace Phinx\Db\Action;

use Phinx\Db\Table\ForeignKey;
use Phinx\Db\Table\Table;

class AddForeignKey extends Action
{

    /**
     * The foreign key to add
     * @var ForeignKey
     */
    protected $foreignKey;

    /**
     * Constructor
     * @param Table      $table The table to add the foreign key to
     * @param ForeignKey $fk    The foreign key to add
     */
    public function __construct(Table $table, ForeignKey $fk)
    {
        parent::__construct($table);
        $this->foreignKey = $fk;
    }

    /**
     * Creates a new AddForeignKey object after building the foreign key with
     * the passed attributes
     * @param Table           $table             The table object to add the foreign key to
     * @param string|string[] $columns           The columns for the foreign key
     * @param Table|string    $referencedTable   The table the foreign key references
     * @param string|array    $referencedColumns The columns in the referenced table
     * @param array           $options           Extra options for the foreign key
     * @param string|null     $name              The name of the foreign key
     * @return AddForeignKey
     */
    public static function build(Table $table, $columns, $referencedTable, $referencedColumns = ['id'], array $options = [], $name = null)
    {
        if (is_string($referencedColumns)) {
            $referencedColumns = [$referencedColumns]; // str to array
        }
        if (is_string($referencedTable)) {
            $referencedTable = new Table($referencedTable);
        }
        $fk = new ForeignKey();
        $fk->setReferencedTable($referencedTable)
           ->setColumns($columns)
           ->setReferencedColumns($referencedColumns)
           ->setOptions($options);
        if ($name !== null) {
            $fk->setConstraint($name);
        }

        return new static($table, $fk);
    }

    /**
     * Returns the foreign key to be added
     * @return ForeignKey
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }
}
