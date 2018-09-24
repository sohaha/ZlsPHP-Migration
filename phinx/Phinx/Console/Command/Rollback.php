<?php

namespace Phinx\Console\Command;

use Z;
use Zls\Migration\Argv as InputInterface;

class Rollback extends AbstractCommand
{
    public function command(InputInterface $input, OutputInterface $output)
    {
        $this->bootstrap($input, $output);
        $version = parent::$target;
        $environment = parent::$environment;
        $date = parent::$date;
        $fake = parent::$fake;
        $force = parent::$force;
        $config = $this->getConfig();
        if ($environment === null) {
            $environment = $config->getDefaultEnvironment();
            $output->writeln($output->warningText('warning') . ' no environment specified, defaulting to: ' . $environment);
        } else {
            $output->writeln($output->infoText('using environment') . ' ' . $environment);
        }
        $envOptions = $config->getEnvironment($environment);
        if (isset($envOptions['adapter'])) {
            $output->writeln($output->infoText('using adapter ') . $envOptions['adapter']);
        }
        if (isset($envOptions['wrapper'])) {
            $output->writeln($output->infoText('using wrapper ') . $envOptions['wrapper']);
        }
        if (isset($envOptions['name'])) {
            $output->writeln($output->infoText('using database ') . $envOptions['name']);
        }
        $versionOrder = $this->getConfig()->getVersionOrder();
        $output->writeln($output->infoText('ordering by ') . $versionOrder . " time");
        if ($fake) {
            $output->writeln($output->warningText('warning') . ' performing fake rollbacks');
        }
        // rollback the specified environment
        if ($date === null) {
            $targetMustMatchVersion = true;
            $target = $version;
        } else {
            $targetMustMatchVersion = false;
            $target = $this->getTargetFromDate($date);
        }
        z::debug('runing');
        $this->getManager()->rollback($environment, $target, $force, $targetMustMatchVersion, $fake);
        $end = z::debug('runing', true);
        $output->writeln('');
        $output->writeln('All Done. ' . $end);
    }

    /**
     * Get Target from Date
     * @param string $date The date to convert to a target.
     * @return string The target
     */
    public function getTargetFromDate($date)
    {
        if (!preg_match('/^\d{4,14}$/', $date)) {
            throw new \InvalidArgumentException('Invalid date. Format is YYYY[MM[DD[HH[II[SS]]]]].');
        }
        // what we need to append to the date according to the possible date string lengths
        $dateStrlenToAppend = [
            14 => '',
            12 => '00',
            10 => '0000',
            8  => '000000',
            6  => '01000000',
            4  => '0101000000',
        ];
        if (!isset($dateStrlenToAppend[strlen($date)])) {
            throw new \InvalidArgumentException('Invalid date. Format is YYYY[MM[DD[HH[II[SS]]]]].');
        }
        $target = $date . $dateStrlenToAppend[strlen($date)];
        $dateTime = \DateTime::createFromFormat('YmdHis', $target);
        if ($dateTime === false) {
            throw new \InvalidArgumentException('Invalid date. Format is YYYY[MM[DD[HH[II[SS]]]]].');
        }

        return $dateTime->format('YmdHis');
    }

    public function description()
    {
        return 'Rollback the last or to a specific migration';
    }

    public function options()
    {
        return [
            '-t, --target' => 'The version number to rollback to',
            '-d, --date'   => 'The date to rollback to',
            '-f, --force'  => 'Force rollback to ignore breakpoints',
            '    --fake'   => 'Mark any rollbacks selected as run, but don\'t actually execute them',
            '    --sql'    => 'Show Sql',
        ];
    }
}
