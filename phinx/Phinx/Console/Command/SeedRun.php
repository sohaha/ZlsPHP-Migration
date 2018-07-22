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
            $output->writeln('<comment>warning</comment> no environment specified, defaulting to: ' . $environment);
        } else {
            $output->writeln('<info>using environment</info> ' . $environment);
        }
        $envOptions = $this->getConfig()->getEnvironment($environment);
        if (isset($envOptions['adapter'])) {
            $output->writeln('<info>using adapter</info> ' . $envOptions['adapter']);
        }
        if (isset($envOptions['wrapper'])) {
            $output->writeln('<info>using wrapper</info> ' . $envOptions['wrapper']);
        }
        if (isset($envOptions['name'])) {
            $output->writeln('<info>using database</info> ' . $envOptions['name']);
        } else {
            $output->writeln('<error>Could not determine database name! Please specify a database name in your config file.</error>');

            return;
        }
        if (isset($envOptions['table_prefix'])) {
            $output->writeln('<info>using table prefix</info> ' . $envOptions['table_prefix']);
        }
        if (isset($envOptions['table_suffix'])) {
            $output->writeln('<info>using table suffix</info> ' . $envOptions['table_suffix']);
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
        $output->writeln('<comment>All Done. Took ' . sprintf('%.4fs', $end - $start) . '</comment>');
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
