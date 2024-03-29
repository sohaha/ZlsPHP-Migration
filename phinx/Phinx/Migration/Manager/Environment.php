<?php

namespace Phinx\Migration\Manager;

use Phinx\Console\Command\OutputInterface;
use Phinx\Db\Adapter\AdapterFactory;
use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Migration\MigrationInterface;
use Phinx\Seed\SeedInterface;
use Zls\Migration\Argv as InputInterface;

class Environment
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var int
     */
    protected $currentVersion;

    /**
     * @var string
     */
    protected $schemaTableName = 'migration_log';

    /**
     * @var \Phinx\Db\Adapter\AdapterInterface
     */
    protected $adapter;

    /**
     * Class Constructor.
     * @param string $name    Environment Name
     * @param array  $options Options
     */
    public function __construct($name, $options)
    {
        $this->name = $name;
        $this->options = $options;
    }

    /**
     * Executes the specified migration on this environment.
     * @param \Phinx\Migration\MigrationInterface $migration Migration
     * @param string                              $direction Direction
     * @param bool                                $fake      flag that if true, we just record running the migration, but not actually do the migration
     * @return void
     */
    public function executeMigration(MigrationInterface $migration, $direction = MigrationInterface::UP, $fake = false)
    {
        $direction = ($direction === MigrationInterface::UP) ? MigrationInterface::UP : MigrationInterface::DOWN;
        $migration->setMigratingUp($direction === MigrationInterface::UP);
        $startTime = time();
        $migration->setAdapter($this->getAdapter());
        if (!$fake) {
            // begin the transaction if the adapter supports it
            if ($this->getAdapter()->hasTransactions()) {
                $this->getAdapter()->beginTransaction();
            }
            // Run the migration
            if (method_exists($migration, MigrationInterface::CHANGE)) {
                if ($direction === MigrationInterface::DOWN) {
                    // Create an instance of the ProxyAdapter so we can record all
                    // of the migration commands for reverse playback
                    /** @var \Phinx\Db\Adapter\ProxyAdapter $proxyAdapter */
                    $proxyAdapter = AdapterFactory::instance()
                                                  ->getWrapper('proxy', $this->getAdapter());
                    $migration->setAdapter($proxyAdapter);
                    /** @noinspection PhpUndefinedMethodInspection */
                    $migration->change();
                    $proxyAdapter->executeInvertedCommands();
                    $migration->setAdapter($this->getAdapter());
                } else {
                    /** @noinspection PhpUndefinedMethodInspection */
                    $migration->change();
                }
            } else {
                $migration->{$direction}();
            }
            // commit the transaction if the adapter supports it
            if ($this->getAdapter()->hasTransactions()) {
                $this->getAdapter()->commitTransaction();
            }
        }
        // Record it in the database
        $this->getAdapter()->migrated($migration, $direction, date('Y-m-d H:i:s', $startTime), date('Y-m-d H:i:s', time()));
    }

    /**
     * Gets the database adapter.
     * @return \Phinx\Db\Adapter\AdapterInterface
     */
    public function getAdapter()
    {
        if (isset($this->adapter)) {
            return $this->adapter;
        }
        if (isset($this->options['connection'])) {
            if (!($this->options['connection'] instanceof \PDO)) {
                throw new \RuntimeException('The specified connection is not a PDO instance');
            }
            $this->options['connection']->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->options['adapter'] = $this->options['connection']->getAttribute(\PDO::ATTR_DRIVER_NAME);
        }
        if (!isset($this->options['adapter'])) {
            throw new \RuntimeException('No adapter was specified for environment: ' . $this->getName());
        }
        $factory = AdapterFactory::instance();
        $adapter = $factory
            ->getAdapter($this->options['adapter'], $this->options);
        // Automatically time the executed commands
        $adapter = $factory->getWrapper('timed', $adapter);
        if (isset($this->options['wrapper'])) {
            $adapter = $factory
                ->getWrapper($this->options['wrapper'], $adapter);
        }
        if ($this->getInput()) {
            $adapter->setInput($this->getInput());
        }
        if ($this->getOutput()) {
            $adapter->setOutput($this->getOutput());
        }
        // Use the TablePrefixAdapter if table prefix/suffixes are in use
        if ($adapter->hasOption('table_prefix') || $adapter->hasOption('table_suffix')) {
            $adapter = AdapterFactory::instance()
                                     ->getWrapper('prefix', $adapter);
        }
        $this->setAdapter($adapter);

        return $adapter;
    }

    /**
     * Sets the database adapter.
     * @param \Phinx\Db\Adapter\AdapterInterface $adapter Database Adapter
     * @return \Phinx\Migration\Manager\Environment
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * Gets the environment name.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the environment's name.
     * @param string $name Environment Name
     * @return \Phinx\Migration\Manager\Environment
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets the console input.
     * @return InputInterface
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Sets the console input.
     * @param InputInterface $input
     * @return $this
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * Gets the console output.
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Sets the console output.
     * @param OutputInterface $output Output
     * @return \Phinx\Migration\Manager\Environment
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Executes the specified seeder on this environment.
     * @param \Phinx\Seed\SeedInterface $seed
     * @return void
     */
    public function executeSeed(SeedInterface $seed)
    {
        $seed->setAdapter($this->getAdapter());
        // begin the transaction if the adapter supports it
        if ($this->getAdapter()->hasTransactions()) {
            $this->getAdapter()->beginTransaction();
        }
        // Run the seeder
        if (method_exists($seed, SeedInterface::RUN)) {
            $seed->run();
        }
        // commit the transaction if the adapter supports it
        if ($this->getAdapter()->hasTransactions()) {
            $this->getAdapter()->commitTransaction();
        }
    }

    /**
     * Gets the environment's options.
     * @return array
     */
    public function getOptions()
    {
        return $this->parseAgnosticDsn($this->options);
    }

    /**
     * Sets the environment's options.
     * @param array $options Environment Options
     * @return \Phinx\Migration\Manager\Environment
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Parse a database-agnostic DSN into individual options.
     * @param array $options Options
     * @return array
     */
    protected function parseAgnosticDsn(array $options)
    {
        if (isset($options['dsn']) && is_string($options['dsn'])) {
            $regex = '#^(?P<adapter>[^\\:]+)\\://(?:(?P<user>[^\\:@]+)(?:\\:(?P<pass>[^@]*))?@)?'
                . '(?P<host>[^\\:@/]+)(?:\\:(?P<port>[1-9]\\d*))?/(?P<name>[^\?]+)(?:\?(?P<query>.*))?$#';
            if (preg_match($regex, trim($options['dsn']), $parsedOptions)) {
                $additionalOpts = [];
                if (isset($parsedOptions['query'])) {
                    parse_str($parsedOptions['query'], $additionalOpts);
                }
                $validOptions = ['adapter', 'user', 'pass', 'host', 'port', 'name'];
                $parsedOptions = array_filter(array_intersect_key($parsedOptions, array_flip($validOptions)));
                $options = array_merge($additionalOpts, $parsedOptions, $options);
                unset($options['dsn']);
            }
        }
        return $options;
    }

    /**
     * Get all migration log entries, indexed by version creation time and sorted in ascending order by the configuration's
     * version_order option
     * @return array
     */
    public function getVersionLog()
    {
        return $this->getAdapter()->getVersionLog();
    }

    /**
     * Gets the current version of the environment.
     * @return int
     */
    public function getCurrentVersion()
    {
        // We don't cache this code as the current version is pretty volatile.
        // TODO - that means they're no point in a setter then?
        // maybe we should cache and call a reset() method every time a migration is run
        $versions = $this->getVersions();
        $version = 0;
        if (!empty($versions)) {
            $version = end($versions);
        }
        $this->setCurrentVersion($version);

        return $this->currentVersion;
    }

    /**
     * Sets the current version of the environment.
     * @param int $version Environment Version
     * @return \Phinx\Migration\Manager\Environment
     */
    public function setCurrentVersion($version)
    {
        $this->currentVersion = $version;

        return $this;
    }

    /**
     * Gets all migrated version numbers.
     * @return array
     */
    public function getVersions()
    {
        return $this->getAdapter()->getVersions();
    }

    /**
     * Gets the schema table name.
     * @return string
     */
    public function getSchemaTableName()
    {
        return $this->schemaTableName;
    }

    /**
     * Sets the schema table name.
     * @param string $schemaTableName Schema Table Name
     * @return \Phinx\Migration\Manager\Environment
     */
    public function setSchemaTableName($schemaTableName)
    {
        $this->schemaTableName = $schemaTableName;

        return $this;
    }
}
