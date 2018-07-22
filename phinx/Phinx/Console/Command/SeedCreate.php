<?php

namespace Phinx\Console\Command;

use Phinx\Config\NamespaceAwareInterface;
use Phinx\Util\Util;
use Z;
use Zls\Migration\Argv as InputInterface;

class SeedCreate extends AbstractCommand
{
    public function command(InputInterface $input, OutputInterface $output)
    {
        $this->bootstrap($input, $output);
        // get the seed path from the config
        $path = $this->getSeedPath($input, $output);
        $this->verifySeedDirectory($path);
        $path = realpath($path);
        $className = z::strSnake2Camel(parent::$name, true);
        if (!Util::isValidPhinxClassName($className)) {
            throw new \InvalidArgumentException(sprintf(
                'The seed class name "%s" is invalid. Please use CamelCase format',
                $className
            ));
        }
        // Compute the file path
        $filePath = $path . DIRECTORY_SEPARATOR . $className . '.php';
        if (is_file($filePath)) {
            throw new \InvalidArgumentException(sprintf(
                'The file "%s" already exists',
                basename($filePath)
            ));
        }
        // inject the class names appropriate to this seeder
        $contents = file_get_contents($this->getSeedTemplateFilename());
        $config = $this->getConfig();
        $namespace = $config instanceof NamespaceAwareInterface ? $config->getSeedNamespaceByPath($path) : null;
        $classes = [
            '$namespaceDefinition' => $namespace !== null ? ('namespace ' . $namespace . ';') : '',
            '$namespace'           => $namespace,
            '$useClassName'        => 'Phinx\Seed\AbstractSeed',
            '$className'           => $className,
            '$baseClassName'       => 'AbstractSeed',
        ];
        $contents = strtr($contents, $classes);
        if (file_put_contents($filePath, $contents) === false) {
            throw new \RuntimeException(sprintf(
                'The file "%s" could not be written to',
                $path
            ));
        }
        $output->writeln($output->infoText('using seed base class ') . $classes['$useClassName']);
        $output->writeln($output->infoText('created .') . str_replace(getcwd(), '', $filePath));
    }


    protected function getSeedPath(InputInterface $input, OutputInterface $output)
    {
        // First, try the non-interactive option:
        $path = $input->get('-path');
        if (!empty($path)) {
            return $path;
        }
        $paths = $this->getConfig()->getSeedPaths();
        // No paths? That's a problem.
        z::throwIf(empty($paths), 'Exception', 'No seed paths set in your configuration file.');
        $paths = Util::globAll($paths);
        z::throwIf(empty($paths), 'Exception',
            'You probably used curly braces to define seed path in your Phinx configuration file, ' .
            'but no directories have been matched using this pattern. ' .
            'You need to create a seed directory manually.');
        // Only one path set, so select that:
        if (1 === count($paths)) {
            return array_shift($paths);
        }
        $output->printStrN();

        return $this->getSelectSeedPathQuestion($paths, $output);
    }

    protected function getSelectSeedPathQuestion(array $paths, OutputInterface $output)
    {
        $tip = 'Which seeds path would you like to use?' . Util::pathsToSelect($paths);
        $value = 0;
        $key = $output->ask($tip, $value, false);
        $path = z::arrayGet($paths, $key);
        if (!$path) {
            $output->printStrN();
            $output->printStrN($output->warningText('warning') . ' Illegal option');
            $path = $this->getSelectSeedPathQuestion($paths, $output);
        }

        return $path;
    }

    public function description()
    {
        return 'Create a new database seeder';
    }

    public function options()
    {
        return [
            '--name' => 'The seed class name',
        ];
    }

}
