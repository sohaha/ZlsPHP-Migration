<?php

namespace Phinx\Db\Adapter;

use Phinx\Console\Command\OutputInterface;
use Phinx\Db\Table;
use Phinx\Db\Table\Column;
use Phinx\Util\Literal;
use Zls\Migration\Argv as InputInterface;

/**
 * Base Abstract Database Adapter.
 */
abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var \Zls_CliArgs
     */
    protected $input;

    /**
     * @var Phinx\Console\Command\OutputInterface
     */
    protected $output;

    /**
     * @var string
     */
    protected $schemaTableName = 'phinxlog';

    /**
     * Class Constructor.
     */
    public function __construct(array $options, InputInterface $input = null, OutputInterface $output = null)
    {
        $this->setOptions($options);
        if ($input !== null) {
            $this->setInput($input);
        }
        if ($output !== null) {
            $this->setOutput($output);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        if (isset($options['default_migration_table'])) {
            $this->setSchemaTableName($options['default_migration_table']);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOutput()
    {
        if ($this->output === null) {
            $output = new OutputInterface();
            $this->setOutput($output);
        }

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
     * @return array
     */
    public function getVersions()
    {
        $rows = $this->getVersionLog();

        return array_keys($rows);
    }

    /**
     * {@inheritdoc}
     */
    public function hasSchemaTable()
    {
        return $this->hasTable($this->getSchemaTableName());
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
     * @return $this
     */
    public function setSchemaTableName($schemaTableName)
    {
        $this->schemaTableName = $schemaTableName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function createSchemaTable()
    {
        try {
            $options = [
                'id'          => false,
                'primary_key' => 'version',
            ];
            $table = new Table($this->getSchemaTableName(), $options, $this);
            $table->addColumn('version', 'biginteger')
                  ->addColumn('migration_name', 'string', ['limit' => 100, 'default' => null, 'null' => true])
                  ->addColumn('start_time', 'timestamp', ['default' => null, 'null' => true])
                  ->addColumn('end_time', 'timestamp', ['default' => null, 'null' => true])
                  ->addColumn('breakpoint', 'boolean', ['default' => false])
                  ->save();
        } catch (\Exception $exception) {
            throw new \InvalidArgumentException(
                'There was a problem creating the schema table: ' . $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAdapterType()
    {
        return $this->getOption('adapter');
    }

    /**
     * {@inheritdoc}
     */
    public function getOption($name)
    {
        if (!$this->hasOption($name)) {
            return null;
        }

        return $this->options[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function hasOption($name)
    {
        return isset($this->options[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function isValidColumnType(Column $column)
    {
        return $column->getType() instanceof Literal || in_array($column->getType(), $this->getColumnTypes());
    }

    /**
     * Determines if instead of executing queries a dump to standard output is needed
     * @return bool
     */
    public function isDryRunEnabled()
    {
        $input = $this->getInput();

        return ($input && $input->get('dry-run')) ? $input->get('dry-run') : false;
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
}
