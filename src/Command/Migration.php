<?php

namespace Zls\Migration\Command;

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
    private $phinxPath;

    public function __construct()
    {
        parent::__construct();
        $this->vendorPath = z::realPath('vendor', true, false);
        $this->configFilePath = __DIR__ . '/../phinx.php';
        $this->phinxPath = $this->vendorPath . 'zls/phinx-package/bin/phinx';
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
        return [
            ' init'        => 'Initialize Phinx Config',
            ' create'      => 'Create a new migration',
            ' migrate'     => 'Migrate the database',
            ' rollback'    => 'Rollback the last or to a specific migration',
            ' breakpoint'  => 'Manage breakpoints',
            ' status'      => 'Show migration status',
            ' seed:create' => 'Create a new database seeder',
            ' seed:run'    => 'Run database seeders',
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

    public function handle()
    {
        return [];
    }

    /**
     * 命令默认执行
     * @param $args
     */
    public function execute($args)
    {
        $args = $this->clearArgs($args);
        $method = z::arrayGet($args, ['type', 2]);
        if ($method) {
            $args = $this->argsCamel($method, $args);
            $argv = $this->args2Str($args);
            $methodChange = z::strSnake2Camel($method, false, ':');
            if (method_exists($this, $methodChange)) {
                $this->$methodChange($args, $argv);
            } else {
                $this->runPhinx($method, $args, $argv);
            }
        } else {
            $this->help($args);
        }
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
        $isCamels = ['create', 'c', 'seed:create', 's:c','seed:c'];
        if (in_array($method, $isCamels)) {
            $name = z::strSnake2Camel(z::arrayGet($args, 3), true);
            $args[3] = $name;
        }

        return $args;
    }

    /**
     * 执行phinx命令
     * @param $method
     * @param $args
     * @param $argv
     */
    private function runPhinx($method, $args, $argv)
    {
        $phinxEnvironment = ['rollback', 'migrate', 'status', 'breakpoint'];
        $ignoreConfiguration = [
            'list',
            'l',
        ];
        if (!in_array($method, $ignoreConfiguration)) {
            $argv .= ' --configuration ' . $this->configFilePath;
        }
        if (in_array($method, $phinxEnvironment)) {
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

    public function init($args)
    {
        $force = Z::arrayGet($args, ['-force', 'F']);
        $path = z::realPath(__DIR__ . '/../migration.ini', false, false);
        $this->copyFile($path, $this->vendorPath . '../migration.ini', $force, function ($state) {
            if (!$state) {
                $this->error('migration.ini already exists');
                $this->printStrN('you can use -force to force the config file');
            } else {
                $this->success('Created Config file migration.ini');
            }
        }, '');
    }
}
