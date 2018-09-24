<?php

namespace Phinx\Console\Command;

use Z;
use Zls\Migration\Argv as InputInterface;

class Migrate extends AbstractCommand
{
    public function command(InputInterface $input, OutputInterface $output)
    {
        $this->bootstrap($input);
        $version = parent::$target;
        $environment = parent::$environment;
        $date = parent::$date;
        $fake = parent::$fake;
        if ($environment === null) {
            $environment = $this->getConfig()->getDefaultEnvironment();
            $output->writeln($output->warningText('warning').' no environment specified, defaulting to: '.$environment);
        } else {
            $output->writeln($output->infoText('using environment').' '.$environment);
        }
        $envOptions = $this->getConfig()->getEnvironment($environment);
        if (isset($envOptions['adapter'])) {
            $output->writeln($output->infoText('using adapter ').$envOptions['adapter']);
        }
        if (isset($envOptions['wrapper'])) {
            $output->writeln($output->infoText('using wrapper ').$envOptions['wrapper']);
        }
        if (isset($envOptions['name'])) {
            $output->writeln($output->infoText('using database ').$envOptions['name']);
        } else {
            $output->writeln($output->errorText('Could not determine database name! Please specify a database name in your config file.'));

            return 1;
        }
        if (isset($envOptions['table_prefix'])) {
            $output->writeln($output->infoText('using table prefix ').$envOptions['table_prefix']);
        }
        if (isset($envOptions['table_suffix'])) {
            $output->writeln($output->infoText('using table suffix ').$envOptions['table_suffix']);
        }
        if ($fake) {
            $output->writeln($output->warningText('warning').' performing fake migrations');
        }
        z::debug('runing');
        if ($date !== null) {
            $this->getManager()->migrateToDateTime($environment, new \DateTime($date), $fake);
        } else {
            $this->getManager()->migrate($environment, $version, $fake);
        }
        $end = z::debug('runing', true);
        $output->writeln('');
        $output->writeln('All Done. '.$end);

        return 0;
    }

    public function description()
    {
        return 'Migrate the database';
    }

    public function options()
    {
        return [
            '-t, --target' => 'The version number to migrate to',
            '-d, --date' => 'The date to migrate to',
            '    --fake' => 'Mark any migrations selected as run, but don\'t actually execute them',
            '    --sql' => 'Show Sql',
        ];
    }
}
