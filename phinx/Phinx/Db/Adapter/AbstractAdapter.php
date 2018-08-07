<?php

namespace Phinx\Db\Adapter;

use Phinx\Console\Command\OutputInterface;
use Phinx\Db\Table;
use Phinx\Db\Table\Column;
use Phinx\Util\Literal;
use Z;
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


    protected $output;

    /**
     * @var string
     */
    protected $schemaTableName = 'phinxlog';


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

    public function getOutput()
    {
        if ($this->output === null) {
            $output = new OutputInterface();
            $this->setOutput($output);
        }

        return $this->output;
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    public function getVersions()
    {
        $rows = $this->getVersionLog();

        return array_keys($rows);
    }

    public function hasSchemaTable()
    {
        return $this->hasTable($this->getSchemaTableName());
    }

    public function getSchemaTableName()
    {
        return $this->schemaTableName;
    }

    /**
     * Sets the schema table name.
     * @return $this
     */
    public function setSchemaTableName($schemaTableName)
    {
        $this->schemaTableName = $schemaTableName;

        return $this;
    }

    public function createSchemaTable()
    {
        $logFields = $this->getLogFields();
        try {
            $options = [
                'id'          => false,
                'primary_key' => z::arrayGet($logFields, 'version'),
            ];
            $table = new Table($this->getSchemaTableName(), $options, $this);
            $table->addColumn(z::arrayGet($logFields, 'version'), 'biginteger', ['comment' => '主键'])
                  ->addColumn(z::arrayGet($logFields, 'migration_name'), 'string', ['comment' => '迁移名称', 'limit' => 100, 'default' => null, 'null' => true])
                  ->addColumn(z::arrayGet($logFields, 'start_time'), 'datetime', ['comment' => '开始时间', 'default' => null, 'null' => true])
                  ->addColumn(z::arrayGet($logFields, 'end_time'), 'datetime', ['comment' => '结束时间', 'default' => null, 'null' => true])
                  ->addColumn(z::arrayGet($logFields, 'breakpoint'), 'boolean', ['comment' => '断点', 'default' => false])
                  ->save();
        } catch (\Exception $exception) {
            throw new \InvalidArgumentException(
                'There was a problem creating the schema table: ' . $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    public function getLogFields()
    {
        $logFields = z::arrayGet($this->getOptions(), 'log_fields');

        return [
            'version'        => z::arrayGet($logFields, 'version', 'version'),
            'migration_name' => z::arrayGet($logFields, 'migration_name', 'migration_name'),
            'start_time'     => z::arrayGet($logFields, 'start_time', 'start_time'),
            'end_time'       => z::arrayGet($logFields, 'end_time', 'end_time'),
            'breakpoint'     => z::arrayGet($logFields, 'breakpoint', 'breakpoint'),
        ];
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
        if (isset($options['default_migration_table'])) {
            $this->setSchemaTableName($options['default_migration_table']);
        }

        return $this;
    }

    public function getAdapterType()
    {
        return $this->getOption('adapter');
    }

    public function getOption($name)
    {
        if (!$this->hasOption($name)) {
            return null;
        }

        return $this->options[$name];
    }

    public function hasOption($name)
    {
        return isset($this->options[$name]);
    }

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

    public function getInput()
    {
        return $this->input;
    }

    public function setInput(InputInterface $input)
    {
        $this->input = $input;

        return $this;
    }
}
