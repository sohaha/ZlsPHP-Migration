<?php

namespace Phinx\Db\Plan;

use Phinx\Db\Action\Action;

/**
 * An intent is a collection of actions for many tables
 */
class Intent
{

    /**
     * List of actions to be executed
     * @var \Phinx\Db\Action\Action[]
     */
    protected $actions = [];

    /**
     * Adds a new action to the collection
     * @param Action $action The action to add
     * @return void
     */
    public function addAction(Action $action)
    {
        $this->actions[] = $action;
    }

    /**
     * Merges another Intent object with this one
     * @param Intent $another The other intent to merge in
     * @return void
     */
    public function merge(Intent $another)
    {
        $this->actions = array_merge($this->actions, $another->getActions());
    }

    /**
     * Returns the full list of actions
     * @return Action[]
     */
    public function getActions()
    {
        return $this->actions;
    }
}
