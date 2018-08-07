<?php

namespace Phinx\Console;

use Phinx\Console\Command\OutputInterface;
use Z;
use Zls\Command\Utils;

/**
 * Phinx console application.
 * @author Rob Morgan <robbym@gmail.com>
 */
class PhinxApplication
{
    use Utils;

    public function run()
    {

        $argv = z::factory('Zls\Migration\Argv', true);
        $args = $argv->get();
        $class = z::arrayGet($args, [2]);
        switch ($class) {
            case 'c':
                $class = 'create';
                break;
            case 'm':
                $class = 'migrate';
                break;
            case 'r':
                $class = 'rollback';
                break;
            case 'b':
                $class = 'breakpoint';
                break;
            case 'seed:c':
            case 's:c':
            case 's:create':
            case 'seed:create':
                $class = 'seed_create';
                break;
            default:
        }
        $method = z::strSnake2Camel($class, true);
        try {
            $className = 'Phinx\Console\Command\\' . $method;
            $obj = z::factory($className);
            $outpu = new OutputInterface();
            if (z::arrayGet($args, ['-help', 'h', 'H'])) {
                $obj->help($args, Z::arrayGet(explode(':', Z::arrayGet($args, 1)), 0) . ' ' . z::strCamel2Snake($method, ':'));
            } else {
                $obj->command($argv, $outpu);
            }
        } catch (\Exception $e) {
            $this->printStrN();
            $err = $e->getMessage();
            if (z::strEndsWith($err, 'not found')) {
                $err = "migration command '{$method}' not found";
            } elseif (strpos($err, 'Phinx\Db\Action\DropTable')) {
                $err = 'Unable to execute, please check if the script is normal';
            } else {
                $err = str_replace('Cannot reverse a "Phinx\Db\Action\RemoveColumn" command', 'Please check the script content is qualified.', $err);
            }
            z::dump($e->getFile(),$e->getLine());
            $this->error($err);
        }

        return '';
    }
}
