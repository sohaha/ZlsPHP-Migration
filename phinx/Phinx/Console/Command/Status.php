<?php

namespace Phinx\Console\Command;

use Zls\Migration\Argv as InputInterface;

class Status extends AbstractCommand
{
    public function command(InputInterface $input, OutputInterface $output)
    {
        $this->bootstrap($input, $output);
        $environment = parent::$environment;
        $format = $input->get(['-format', 'f']);
        if ($format === '1') {
            $format = 'json';
        }
        if ($environment === null) {
            $environment = $this->getConfig()->getDefaultEnvironment();
            $output->writeln($output->warningText('warning') . ' no environment specified, defaulting to: ' . $environment);
        } else {
            $output->writeln($output->infoText('using environment ') . $environment);
        }
        if ($format !== null) {
            $output->writeln($output->infoText('using format ') . $format);
        }
        $output->writeln($output->infoText('ordering by ') . $this->getConfig()->getVersionOrder() . " time");

        return $this->getManager()->printStatus($environment, $format);
    }

    public function description()
    {
        return 'Show migration status';
    }

    public function options()
    {
        return [
            '--format, -f' => 'The output format: text or json. Defaults to text?',
        ];
    }
}
