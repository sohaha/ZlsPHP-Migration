<?php

namespace Phinx\Console\Command;

use Zls\Command\Utils;

class OutputInterface
{
    use Utils;

    const VERBOSITY_QUIET = 16;
    const VERBOSITY_NORMAL = 32;
    const VERBOSITY_VERBOSE = 64;
    const VERBOSITY_VERY_VERBOSE = 128;
    const VERBOSITY_DEBUG = 256;
    private $verbosity = 0;

    //private  function __construct()
    //{
    //    $argv = z::getOpt();
    //    $this->verbosity = z::arrayGet($argv, ['-q'], 0);
    //}
    public function writeln($str, $color = 'green')
    {
        $strs = is_array($str) ? $str : [$str];
        foreach ($strs as $str) {
            $this->printStrN($str, $color);
        }
    }

    public function infoText($str)
    {
        return $this->colorText($str, 'green', '');
    }

    public function colorText($str = '', $color = null, $bgColor = null)
    {
        return $this->color($str, $color, $bgColor);
    }

    public function tipText($str)
    {
        return $this->colorText($str, 'light_cyan');
    }

    public function warningText($str)
    {
        return $this->colorText($str, 'yellow', '');
    }

    public function errorText($str)
    {
        return $this->colorText($str, 'red', '');
    }

    public function getVerbosity()
    {
        return $this->verbosity;
    }
}
