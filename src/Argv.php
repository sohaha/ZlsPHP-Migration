<?php

namespace Zls\Migration;

use Z;
use Zls_CliArgs;

class Argv extends Zls_CliArgs
{
    public $args;

    public function __construct()
    {
        parent::__construct();
        $args = Z::getOpt();
        $this->args = empty($args) ? [] : $args;
    }

    public function get($key = null, $default = null)
    {
        if (empty($key)) {
            return $this->args;
        }

        return Z::arrayGet($this->args, $key, $default);
    }

    public function set($args)
    {
        $GLOBALS['argv'] = $args;
        $this->args = $args;
    }
}
