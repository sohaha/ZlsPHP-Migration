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
    private $defaultConfigPath;
    private $phinxPath;

    public function __construct()
    {
        parent::__construct();
        $this->vendorPath = z::realPath('vendor', true, false);
        $this->defaultConfigPath = __DIR__ . '/../phinx.php';
        $this->configFilePath = $this->getConfigFilePath();
        $this->phinxPath = $this->vendorPath . 'robmorgan/phinx/bin/phinx';
    }

    private function getConfigFilePath()
    {
        $projectPhinxPath = z::realPath('phinx.php', false, false);

        return is_file($projectPhinxPath) ? $projectPhinxPath : $this->defaultConfigPath;
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
        $method = z::arrayGet($args, ['type', 2]);
        if ($method) {
            $argv = $this->args2Str($args);
            if (method_exists($this, $method)) {
                $this->$method($args, $argv);
            } else {
                $this->runPhinx($method, $args, $argv);
            }
        } else {
            $this->help($args);
        }
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

    private function runPhinx($method, $args, $argv)
    {
        $camelCase = ['create', 'seed:create'];
        if (in_array($method, $camelCase)) {
            $name = z::strSnake2Camel(z::arrayGet($args, 3), true);
            $args[3] = $name;
            $argv = $this->args2Str($args);
        }
        $phinxCmd = ['rollback', 'migrate', 'status', 'create'];
        $phinxEnvironment = ['rollback', 'migrate', 'status', 'breakpoint'];
        if (in_array($method, $phinxCmd)) {
            $argv .= ' --configuration ' . $this->configFilePath;
        }
        if (in_array($method, $phinxEnvironment)) {
            $argv .= ' -e production';
        }
        $cmd = " {$this->phinxPath} {$method} {$argv}";
        $result = z::command(z::phpPath() . $cmd);
        $this->printStrN($this->cmdResult($result));
    }

    private function cmdResult($str)
    {
        return $str;
    }

    public function init()
    {
        $projectPhinxPath = z::realPath('phinx.php', false, false);
        $isExists = false;
        if (is_file($projectPhinxPath)) {
            $isExists = true;
            $this->error('Config file phinx.php already exists.');
            //$this->printStrN();
            //$result = $this->ask('Whether to overwrite the current configuration [y|N]:', 'n');
            //$isExists = !($result === 'y');
        }
        if (!$isExists) {
            $content = file_get_contents($this->defaultConfigPath);
            $content = str_replace('/../../../../application/', '/./application/', $content);
            if (@file_put_contents($projectPhinxPath, $content)) {
                $this->success('Created Config file phinx.php');
            } else {
                $this->error('Created Error');
            }
        }
    }
}
