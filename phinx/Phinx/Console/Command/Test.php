<?php

namespace Phinx\Console\Command;

use Phinx\Migration\Manager\Environment;
use Phinx\Util\Util;
use Zls\Migration\Argv as InputInterface;

/**
 * Verify the configuration file
 */
class Test extends AbstractCommand
{
    public function command(InputInterface $input, OutputInterface $output)
    {
        $this->loadConfig($input, $output);
        $this->loadManager($input, $output);
        // Verify the migrations path(s)
        array_map(
            [$this, 'verifyMigrationDirectory'],
            Util::globAll($this->getConfig()->getMigrationPaths())
        );
        // Verify the seed path(s)
        array_map(
            [$this, 'verifySeedDirectory'],
            Util::globAll($this->getConfig()->getSeedPaths())
        );
        $envName = parent::$environment;
        if ($envName) {
            if (!$this->getConfig()->hasEnvironment($envName)) {
                throw new \InvalidArgumentException(sprintf(
                    'The environment "%s" does not exist',
                    $envName
                ));
            }
            $output->writeln(sprintf($output->infoText('validating environment %s'), $envName));
            $environment = new Environment(
                $envName,
                $this->getConfig()->getEnvironment($envName)
            );
            // validate environment connection
            $environment->getAdapter()->connect();
        }
        $output->writeln($output->infoText('success!'));
    }
}
