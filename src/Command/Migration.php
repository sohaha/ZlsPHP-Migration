<?php

namespace Zls\Migration\Command;

use Phinx\Console\PhinxApplication;
use Z;
use Zls\Command\Command;

/**
 * Zls
 * @author        影浅
 * @email         seekwe@gmail.com
 * @copyright     Copyright (c) 2015 - 2017, 影浅, Inc.
 * @link          ---
 * @since         v0.0.1
 * @updatetime    2018-07-17 15:31
 */
class Migration extends Command
{
    private $vendorPath;
    private $configFilePath;

    public function __construct()
    {
        parent::__construct();
        $this->vendorPath = getcwd() . '/vendor/';
        $this->configFilePath = __DIR__ . '/../phinx.php';
    }


    /**
     * 命令配置
     * @return array
     */
    public function options()
    {
        return [];
    }

    public function example()
    {
        return [];
    }

    /**
     * @param      $args
     * @param null $command
     */
    public function help($args, $command = null)
    {
        $this->execute($args);
    }

    /**
     * 命令默认执行
     * @param $args
     */
    public function execute($args)
    {
        $method = z::arrayGet($args, 2);
        if ($method) {
            if (method_exists($this, $method)) {
                $this->$method($args);
            } else {
                $this->ex($args);
                $phinx = new PhinxApplication();
                $phinx->run();
            }
        } else {
            parent::help($args);
        }
    }

    protected function ex($argv)
    {
        $name = z::arrayGet($argv, 1, '');
        if (strpos($name, ':') !== false) {
            list($a1, $a2) = explode(':', $name);
            $argv[1] = $a1;
            array_splice($argv, 2, 0, $a2);
            $ArgvObj = z::factory('Zls\Migration\Argv', true);
            $ArgvObj->set($argv);
        }

        return $argv;
    }

    public function handle()
    {
        return [
            'init'        => 'Initialize Phinx Config',
            'create'      => 'Create a new migration',
            'migrate'     => 'Migrate the database',
            'rollback'    => 'Rollback the last or to a specific migration',
            'breakpoint'  => 'Manage breakpoints',
            'status'      => 'Show migration status',
            'seed:create' => 'Create a new database seeder',
            'seed:run'    => 'Run database seeders',
        ];
    }

    /**
     * 命令介绍
     * @return string
     */
    public function description()
    {
        return 'Easy to manage the database migrations';
    }

    public function __call($method, $args)
    {
        $argv = $this->ex(z::arrayGet($args, 0, []));
        $this->execute($argv);
    }

    public function init($args)
    {
        $force = Z::arrayGet($args, ['-force', 'F']);
        $path = z::realPath(__DIR__ . '/../migration.ini', false, false);
        $this->copyFile($path, $this->vendorPath . '../migration.ini', $force, function ($state) {
            if (!$state) {
                $this->error('migration.ini already exists');
                $this->printStrN('you can use --force to force the config file');
            } else {
                $this->success('Created Config file migration.ini');
            }
        }, '');
    }

    private function clearArgs($args)
    {
        if (z::arrayGet($args, ['-h'])) {
            unset($args['-h']);
            $args['help'] = true;
        }

        return $args;
    }

    private function args2Str($args)
    {
        $argv = [];
        $tmpArgs = array_slice($args, 3);
        $jump = ['-configuration', 'e'];
        foreach ($tmpArgs as $k => $v) {
            if (in_array($k, $jump, true)) {
                continue;
            }
            $argv[] = is_numeric($k) ? $v : "-{$k} {$v}";
        }

        return join(' ', $argv);
    }

    private function argsCamel($method, $args)
    {
        $isCamels = ['create', 'c', 'seed:create', 's:c', 'seed:c'];
        if (in_array($method, $isCamels)) {
            $name = z::strSnake2Camel(z::arrayGet($args, 3), true);
            $args[3] = $name;
        }

        return $args;
    }

    private function runPhinx($method, $args, $argv)
    {
        $phinxEnvironment = ['rollback', 'migrate', 'status', 'breakpoint', 'm', 'r'];
        $ignoreConfiguration = [
            'list',
            'l',
        ];
        $ignoreEnvironment = [
            'list',
            'l',
            'create',
            'c',
            'seed:create',
            'seed:c',
            's:c',
        ];
        if (!in_array($method, $ignoreConfiguration)) {
            $argv .= ' --configuration ' . $this->configFilePath;
        }
        if (!in_array($method, $ignoreEnvironment)) {
            $argv .= ' -e production';
        }
        $cmd = z::phpPath() . " {$this->phinxPath} {$method} {$argv}";
        if (z::arrayGet($args, '-debug')) {
            $this->printStrN($cmd);
        } else {
            $result = z::command($cmd);
            $this->printStrN($this->cmdResult($result));
        }
    }

    private function cmdResult($str)
    {
        return $str;
    }
}
