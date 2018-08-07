<?php

namespace Phinx\Migration;

use Phinx\Console\Command\OutputInterface;
use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Table;
use Zls\Migration\Argv as InputInterface;

abstract class AbstractMigration implements MigrationInterface
{
    /**
     * @var string
     */
    protected $environment;
    /**
     * @var float
     */
    protected $version;

    /**
     * @var \Phinx\Db\Adapter\AdapterInterface
     */
    protected $adapter;

    /**
     * @var \Phinx\Console\Command\OutputInterface
     */
    protected $output;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * Whether this migration is being applied or reverted
     * @var bool
     */
    protected $isMigratingUp = true;

    /**
     * Class Constructor.
     * @param string          $environment Environment Detected
     * @param int             $version     Migration Version
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    final public function __construct($environment, $version, InputInterface $input = null, OutputInterface $output = null)
    {
        $this->environment = $environment;
        $this->version = $version;
        if (!is_null($input)) {
            $this->setInput($input);
        }
        if (!is_null($output)) {
            $this->setOutput($output);
        }
        $this->init();
    }

    /**
     * Initialize method.
     * @return void
     */
    protected function init()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * {@inheritdoc}
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * {@inheritdoc}
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return get_class($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * {@inheritdoc}
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setMigratingUp($isMigratingUp)
    {
        $this->isMigratingUp = $isMigratingUp;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isMigratingUp()
    {
        return $this->isMigratingUp;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($sql)
    {
        return $this->getAdapter()->execute($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function query($sql)
    {
        return $this->getAdapter()->query($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryBuilder()
    {
        return $this->getAdapter()->getQueryBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchRow($sql)
    {
        return $this->getAdapter()->fetchRow($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll($sql)
    {
        return $this->getAdapter()->fetchAll($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function insert($table, $data)
    {
        trigger_error('insert() is deprecated since 0.10.0. Use $this->table($tableName)->insert($data)->save() instead.', E_USER_DEPRECATED);
        // convert to table object
        if (is_string($table)) {
            $table = new Table($table, [], $this->getAdapter());
        }
        $table->insert($data)->save();
    }

    /**
     * {@inheritdoc}
     */
    public function createDatabase($name, $options)
    {
        $this->getAdapter()->createDatabase($name, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function dropDatabase($name)
    {
        $this->getAdapter()->dropDatabase($name);
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable($tableName)
    {
        return $this->getAdapter()->hasTable($tableName);
    }

    /**
     * A short-hand method to drop the given database table.
     * @deprecated since 0.10.0. Use $this->table($tableName)->drop()->save() instead.
     * @param string $tableName Table Name
     * @return void
     */
    public function dropTable($tableName)
    {
        trigger_error('dropTable() is deprecated since 0.10.0. Use $this->table($tableName)->drop()->save() instead.', E_USER_DEPRECATED);
        $this->table($tableName)->drop()->save();
    }

    /**
     * {@inheritdoc}
     */
    public function table($tableName, $options = [])
    {
        return new Table($tableName, $options, $this->getAdapter());
    }

    /**
     * Perform checks on the migration, print a warning
     * if there are potential problems.
     * Right now, the only check is if there is both a `change()` and
     * an `up()` or a `down()` method.
     * @param string|null $direction
     * @return void
     */
    public function preFlightCheck($direction = null)
    {
        if (method_exists($this, MigrationInterface::CHANGE)) {
            if (method_exists($this, MigrationInterface::UP) ||
                method_exists($this, MigrationInterface::DOWN)) {
                $this->output->writeln(sprintf(
                    $this->output->warningText('Migration contains both change() and/or up()/down() methods.').' Ignoring up() and down().'
                ));
            }
        }
    }
}
