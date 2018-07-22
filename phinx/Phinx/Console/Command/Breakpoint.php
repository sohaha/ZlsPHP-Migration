<?php

namespace Phinx\Console\Command;

use Zls\Migration\Argv as InputInterface;

class Breakpoint extends AbstractCommand
{
    /**
     * Toggle the breakpoint.
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return void
     */
    public function command(InputInterface $input, OutputInterface $output)
    {
        $this->bootstrap($input, $output);
        $environment = parent::$environment;
        $version = parent::$target;
        $removeAll = $input->get(['-remove-all', 'r']);
        if ($environment === null) {
            $environment = $this->getConfig()->getDefaultEnvironment();
            $output->writeln($output->warningText('warning') . ' no environment specified, defaulting to: ' . $environment);
        } else {
            $output->writeln($output->infoText('using environment ' . $environment));
        }
        if ($version && $removeAll) {
            throw new \InvalidArgumentException('Cannot toggle a breakpoint and remove all breakpoints at the same time.');
        }
        // Remove all breakpoints
        if ($removeAll) {
            $this->getManager()->removeBreakpoints($environment);
        } else {
            // Toggle the breakpoint.
            $this->getManager()->toggleBreakpoint($environment, $version);
        }
    }

    public function description()
    {
        return 'Manage breakpoints';
    }

    public function options()
    {
        return [
            '-t, --target'     => 'The version number to set or clear a breakpoint against',
            '    --remove-all' => 'Remove all breakpoints',
        ];
    }
}
