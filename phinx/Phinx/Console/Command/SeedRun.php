<?php

namespace Phinx\Console\Command;

use Zls\Migration\Argv as InputInterface;

/**
 * Run database seeders
 * @package Phinx\Console\Command
 */
class SeedRun extends AbstractCommand
{
    public function command(InputInterface $input, OutputInterface $output)
    {
        $this->bootstrap($input, $output);
        $seedSet = $input->get(['seed']);
        $environment = parent::$environment;
        if ($environment === null) {
            $environment = $this->getConfig()->getDefaultEnvironment();
            $output->writeln($output->warningText('warning') . ' no environment specified, defaulting to: ' . $environment);
        } else {
            $output->writeln($output->infoText('using environment ') . $environment);
        }
        $envOptions = $this->getConfig()->getEnvironment($environment);
        if (isset($envOptions['adapter'])) {
            $output->writeln($output->infoText('using adapter ') . $envOptions['adapter']);
        }
        if (isset($envOptions['wrapper'])) {
            $output->writeln($output->infoText('using wrapper ') . $envOptions['wrapper']);
        }
        if (isset($envOptions['name'])) {
            $output->writeln($output->infoText('using database ') . $envOptions['name']);
        } else {
            $output->writeln($output->errorText('Could not determine database name! Please specify a database name in your config file.'));

            return;
        }
        if (isset($envOptions['table_prefix'])) {
            $output->writeln($output->infoText('using table prefix ') . $envOptions['table_prefix']);
        }
        if (isset($envOptions['table_suffix'])) {
            $output->writeln($output->infoText('using table suffix ') . $envOptions['table_suffix']);
        }
        $start = microtime(true);
        if (empty($seedSet)) {
            // run all the seed(ers)
            $this->getManager()->seed($environment);
        } else {
            // run seed(ers) specified in a comma-separated list of classes
            foreach ($seedSet as $seed) {
                $this->getManager()->seed($environment, trim($seed));
            }
        }
        $end = microtime(true);
        $output->writeln('');
        $output->writeln($output->tipText('All Done. Took ' . sprintf('%.4fs', $end - $start)));
    }

    public function description()
    {
        return 'Run database seeders';
    }

    public function options()
    {
        return [
            '--seed, -s' => 'What is the name of the seeder?',
        ];
    }
}
