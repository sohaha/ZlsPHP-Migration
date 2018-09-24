<?php

namespace Phinx\Console\Command;

use Phinx\Config\NamespaceAwareInterface;
use Phinx\Util\Util;
use Z;
use Zls\Migration\Argv as InputInterface;

class Create extends AbstractCommand
{
    /**
     * The name of the interface that any external template creation class is required to implement.
     */
    const CREATION_INTERFACE = 'Phinx\Migration\CreationInterface';

    public function command(InputInterface $input, OutputInterface $output)
    {
        $this->bootstrap($input, $output);
        $path = $this->getMigrationPath($input, $output);
        $this->verifyMigrationDirectory($path);
        $config = $this->getConfig();
        $namespace = $config instanceof NamespaceAwareInterface ? $config->getMigrationNamespaceByPath($path) : null;
        $path = realpath($path);
        $className = z::strSnake2Camel(parent::$name, true);
        if (!Util::isValidPhinxClassName($className)) {
            throw new \InvalidArgumentException(sprintf(
                'The migration class name "%s" is invalid. Please use CamelCase format.',
                $className
            ));
        }
        if (!Util::isUniqueMigrationClassName($className, $path)) {
            throw new \InvalidArgumentException(sprintf(
                'The migration class name "%s%s" already exists',
                $namespace ? ($namespace . '\\') : '',
                $className
            ));
        }
        // Compute the file path
        $fileName = Util::mapClassNameToFileName($className);
        $filePath = $path . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($filePath)) {
            throw new \InvalidArgumentException(sprintf(
                'The file "%s" already exists',
                $filePath
            ));
        }
        // Get the alternative template and static class options from the config, but only allow one of them.
        $defaultAltTemplate = $this->getConfig()->getTemplateFile();
        $defaultCreationClassName = $this->getConfig()->getTemplateClass();
        if ($defaultAltTemplate && $defaultCreationClassName) {
            throw new \InvalidArgumentException('Cannot define template:class and template:file at the same time');
        }
        // Get the alternative template and static class options from the command line, but only allow one of them.
        $altTemplate = $input->get('-template');
        $creationClassName = $input->get('-class');
        if ($altTemplate && $creationClassName) {
            throw new \InvalidArgumentException('Cannot use --template and --class at the same time');
        }
        // If no commandline options then use the defaults.
        if (!$altTemplate && !$creationClassName) {
            $altTemplate = $defaultAltTemplate;
            $creationClassName = $defaultCreationClassName;
        }
        // Verify the alternative template file's existence.
        if ($altTemplate && !is_file($altTemplate)) {
            throw new \InvalidArgumentException(sprintf(
                'The alternative template file "%s" does not exist',
                $altTemplate
            ));
        }
        // Verify that the template creation class (or the aliased class) exists and that it implements the required interface.
        $aliasedClassName = null;
        if ($creationClassName) {
            // Supplied class does not exist, is it aliased?
            if (!class_exists($creationClassName)) {
                $aliasedClassName = $this->getConfig()->getAlias($creationClassName);
                if ($aliasedClassName && !class_exists($aliasedClassName)) {
                    throw new \InvalidArgumentException(sprintf(
                        'The class "%s" via the alias "%s" does not exist',
                        $aliasedClassName,
                        $creationClassName
                    ));
                } elseif (!$aliasedClassName) {
                    throw new \InvalidArgumentException(sprintf(
                        'The class "%s" does not exist',
                        $creationClassName
                    ));
                }
            }
            // Does the class implement the required interface?
            if (!$aliasedClassName && !is_subclass_of($creationClassName, self::CREATION_INTERFACE)) {
                throw new \InvalidArgumentException(sprintf(
                    'The class "%s" does not implement the required interface "%s"',
                    $creationClassName,
                    self::CREATION_INTERFACE
                ));
            } elseif ($aliasedClassName && !is_subclass_of($aliasedClassName, self::CREATION_INTERFACE)) {
                throw new \InvalidArgumentException(sprintf(
                    'The class "%s" via the alias "%s" does not implement the required interface "%s"',
                    $aliasedClassName,
                    $creationClassName,
                    self::CREATION_INTERFACE
                ));
            }
        }
        // Use the aliased class.
        $creationClassName = $aliasedClassName ?: $creationClassName;
        // Determine the appropriate mechanism to get the template
        if ($creationClassName) {
            // Get the template from the creation class
            $creationClass = new $creationClassName($input, $output);
            $contents = $creationClass->getMigrationTemplate();
        } else {
            // Load the alternative template if it is defined.
            $contents = file_get_contents($altTemplate ?: $this->getMigrationTemplateFilename());
        }
        // inject the class names appropriate to this migration
        $classes = [
            '$namespaceDefinition' => $namespace !== null ? ('namespace ' . $namespace . ';') : '',
            '$namespace'           => $namespace,
            '$useClassName'        => $this->getConfig()->getMigrationBaseClassName(false),
            '$className'           => $className,
            '$version'             => Util::getVersionFromFileName($fileName),
            '$baseClassName'       => $this->getConfig()->getMigrationBaseClassName(true),
        ];
        $contents = strtr($contents, $classes);
        if (file_put_contents($filePath, $contents) === false) {
            throw new \RuntimeException(sprintf(
                'The file "%s" could not be written to',
                $path
            ));
        }
        // Do we need to do the post creation call to the creation class?
        if (isset($creationClass)) {
            $creationClass->postMigrationCreation($filePath, $className, $this->getConfig()->getMigrationBaseClassName());
        }
        $output->writeln($output->infoText('using migration base class ') . $classes['$useClassName']);
        if (!empty($altTemplate)) {
            $output->writeln($output->infoText('using alternative template ') . $altTemplate);
        } elseif (!empty($creationClassName)) {
            $output->writeln($output->infoText('using template creation class ') . $creationClassName);
        } else {
            $output->writeln($output->infoText('using default template '));
        }
        $output->writeln($output->infoText('created ') . str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $filePath));
    }

    /**
     * Returns the migration path to create the migration in.
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function getMigrationPath(InputInterface $input, OutputInterface $output)
    {
        // First, try the non-interactive option:
        $path = $input->get('path');
        if (!empty($path)) {
            return $path;
        }
        $paths = $this->getConfig()->getMigrationPaths();
        // No paths? That's a problem.
        z::throwIf(empty($paths), 'Exception', 'No migration paths set in your Phinx configuration file.');
        $paths = Util::globAll($paths);
        z::throwIf(
            empty($paths),
            'Exception',
            'You probably used curly braces to define migration path in your Phinx configuration file, ' .
            'but no directories have been matched using this pattern. ' .
            'You need to create a migration directory manually.'
        );
        // Only one path set, so select that:
        if (1 === count($paths)) {
            return array_shift($paths);
        }

        return $this->getSelectMigrationPathQuestion($paths, $output);
    }


    protected function getSelectMigrationPathQuestion(array $paths, OutputInterface $output)
    {
        $tip = 'Which migrations path would you like to use?' . Util::pathsToSelect($paths);
        $value = 0;
        $key = $output->ask($tip, $value, false);
        $path = z::arrayGet($paths, $key);
        if (!$path) {
            $output->printStrN();
            $output->printStrN($output->warningText('warning') . ' Illegal option');
            $path = $this->getSelectMigrationPathQuestion($paths, $output);
        }

        return $path;
    }

    public function description()
    {
        return 'Create a new migration';
    }

    public function options()
    {
        return [
            '--name' => 'The migration class name',
        ];
    }
}
