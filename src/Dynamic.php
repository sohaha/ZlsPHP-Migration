<?php

namespace Zls\Migration;

use Phinx\Config\Config;
use Phinx\Console\Command\AbstractCommand;
use Phinx\Console\Command\Migrate;
use Phinx\Console\Command\OutputInterface;
use Phinx\Migration\Manager;
use Z;

class Dynamic
{
    public static function run($table, array $data, $comment = '', $processing = null)
    {
        /** @var Migrate $obj */
        $obj = Z::factory("\Phinx\Console\Command\Migrate", true);
        $outpu = new OutputInterface();
        $input = new Argv();
        $configFilePath = Z::realPath(Z::realPath('.', false, false) . AbstractCommand::CONFIGURATION_PATH);
        $config = Config::fromPhp($configFilePath);
        $obj->setConfig($config);
        $manager = new Manager($obj->getConfig(), $input, $outpu);
        $environment = "production";
        $migration = new Custom($environment, null, $input, $outpu);
        if (empty($migration)) {
            return "migration invalid";
        }
        $env = $manager->getEnvironment($environment);
        $migration->setAdapter($env->getAdapter());
        try {
            $migration->up($table, $data, $comment, $processing);
            return null;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
