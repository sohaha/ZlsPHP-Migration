<?php

namespace Phinx\Db\Table;

class ForeignKey
{
    const CASCADE = 'CASCADE';
    const RESTRICT = 'RESTRICT';
    const SET_NULL = 'SET NULL';
    const NO_ACTION = 'NO ACTION';

    /**
     * @var array
     */
    protected $columns = [];

    /**
     * @var \Phinx\Db\Table\Table
     */
    protected $referencedTable;

    /**
     * @var array
     */
    protected $referencedColumns = [];

    /**
     * @var string
     */
    protected $onDelete;

    /**
     * @var string
     */
    protected $onUpdate;

    /**
     * @var string|boolean
     */
    protected $constraint;

    /**
     * Gets the foreign key columns.
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Sets the foreign key columns.
     * @param array|string $columns
     * @return \Phinx\Db\Table\ForeignKey
     */
    public function setColumns($columns)
    {
        $this->columns = is_string($columns) ? [$columns] : $columns;

        return $this;
    }

    /**
     * Gets the foreign key referenced table.
     * @return \Phinx\Db\Table\Table
     */
    public function getReferencedTable()
    {
        return $this->referencedTable;
    }

    /**
     * Sets the foreign key referenced table.
     * @param \Phinx\Db\Table\Table $table The table this KEY is pointing to
     * @return \Phinx\Db\Table\ForeignKey
     */
    public function setReferencedTable(Table $table)
    {
        $this->referencedTable = $table;

        return $this;
    }

    /**
     * Gets the foreign key referenced columns.
     * @return array
     */
    public function getReferencedColumns()
    {
        return $this->referencedColumns;
    }

    /**
     * Sets the foreign key referenced columns.
     * @param array $referencedColumns
     * @return \Phinx\Db\Table\ForeignKey
     */
    public function setReferencedColumns(array $referencedColumns)
    {
        $this->referencedColumns = $referencedColumns;

        return $this;
    }

    /**
     * Gets ON DELETE action for the foreign key.
     * @return string
     */
    public function getOnDelete()
    {
        return $this->onDelete;
    }

    /**
     * Sets ON DELETE action for the foreign key.
     * @param string $onDelete
     * @return \Phinx\Db\Table\ForeignKey
     */
    public function setOnDelete($onDelete)
    {
        $this->onDelete = $this->normalizeAction($onDelete);

        return $this;
    }

    /**
     * Gets ON UPDATE action for the foreign key.
     * @return string
     */
    public function getOnUpdate()
    {
        return $this->onUpdate;
    }

    /**
     * Sets ON UPDATE action for the foreign key.
     * @param string $onUpdate
     * @return \Phinx\Db\Table\ForeignKey
     */
    public function setOnUpdate($onUpdate)
    {
        $this->onUpdate = $this->normalizeAction($onUpdate);

        return $this;
    }

    /**
     * Gets constraint name for the foreign key.
     * @return string|bool
     */
    public function getConstraint()
    {
        return $this->constraint;
    }

    /**
     * Sets constraint for the foreign key.
     * @param string $constraint
     * @return \Phinx\Db\Table\ForeignKey
     */
    public function setConstraint($constraint)
    {
        $this->constraint = $constraint;

        return $this;
    }

    /**
     * Utility method that maps an array of index options to this objects methods.
     * @param array $options Options
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return \Phinx\Db\Table\ForeignKey
     */
    public function setOptions($options)
    {
        // Valid Options
        $validOptions = ['delete', 'update', 'constraint'];
        foreach ($options as $option => $value) {
            if (!in_array($option, $validOptions, true)) {
                throw new \RuntimeException(sprintf('"%s" is not a valid foreign key option.', $option));
            }
            // handle $options['delete'] as $options['update']
            if ('delete' === $option) {
                $this->setOnDelete($value);
            } elseif ('update' === $option) {
                $this->setOnUpdate($value);
            } else {
                $method = 'set' . ucfirst($option);
                $this->$method($value);
            }
        }

        return $this;
    }

    /**
     * From passed value checks if it's correct and fixes if needed
     * @param string $action
     * @throws \InvalidArgumentException
     * @return string
     */
    protected function normalizeAction($action)
    {
        $constantName = 'static::' . str_replace(' ', '_', strtoupper(trim($action)));
        if (!defined($constantName)) {
            throw new \InvalidArgumentException('Unknown action passed: ' . $action);
        }

        return constant($constantName);
    }
}
