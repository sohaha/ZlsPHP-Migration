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
    /** @var Custom */
    private $migration;

    public function __construct()
    {
        /** @var Migrate $obj */
        $obj = Z::factory('\Phinx\Console\Command\Migrate', true);
        $outpu = new OutputInterface();
        $input = new Argv();
        $configFilePath = Z::realPath(Z::realPath('.', false, false) . AbstractCommand::CONFIGURATION_PATH);
        $config = Config::fromPhp($configFilePath);
        $obj->setConfig($config);
        $manager = new Manager($obj->getConfig(), $input, $outpu);
        $environment = 'production';
        $migration = new Custom($environment, null, $input, $outpu);
        Z::throwIf(empty($migration), 500, 'migration invalid');
        $env = $manager->getEnvironment($environment);
        $migration->setAdapter($env->getAdapter());
        $this->migration = $migration;
    }

    /**
     * @return Custom
     */
    public function getMigration()
    {
        return $this->migration;
    }

    public static function instance()
    {
        static $instance;
        if (!$instance) {
            $instance = new self();
        }
        return $instance;
    }

    public static function run($table, array $data, $comment = '', $processing = null)
    {
        $migration = self::instance()->getMigration();
        try {
            $migration->up($table, $data, $comment, $processing);
            return null;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public static function migration($table)
    {
        return self::instance()->getMigration()->table($table);
    }

    public static function exists($table)
    {
        $migration = self::instance()->getMigration();
        return $migration->table($table)->exists();
    }
}
