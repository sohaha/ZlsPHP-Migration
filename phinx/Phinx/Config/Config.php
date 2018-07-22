<?php

namespace Phinx\Config;

/**
 * Phinx configuration class.
 * @package Phinx
 * @author  Rob Morgan
 */
class Config implements ConfigInterface, NamespaceAwareInterface
{
    use NamespaceAwareTrait;

    /**
     * The value that identifies a version order by creation time.
     */
    const VERSION_ORDER_CREATION_TIME = 'creation';

    /**
     * The value that identifies a version order by execution time.
     */
    const VERSION_ORDER_EXECUTION_TIME = 'execution';
    /**
     * @var string
     */
    protected $configFilePath;
    /**
     * @var array
     */
    private $values = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(array $configArray, $configFilePath = null)
    {
        $this->configFilePath = $configFilePath;
        $this->values = $this->replaceTokens($configArray);
    }

    /**
     * Replace tokens in the specified array.
     * @param array $arr Array to replace
     * @return array
     */
    protected function replaceTokens(array $arr)
    {
        // Get environment variables
        // $_ENV is empty because variables_order does not include it normally
        $tokens = [];
        foreach ($_SERVER as $varname => $varvalue) {
            if (0 === strpos($varname, 'PHINX_')) {
                $tokens['%%' . $varname . '%%'] = $varvalue;
            }
        }
        // Phinx defined tokens (override env tokens)
        $tokens['%%PHINX_CONFIG_PATH%%'] = $this->getConfigFilePath();
        $tokens['%%PHINX_CONFIG_DIR%%'] = dirname($this->getConfigFilePath());

        // Recurse the array and replace tokens
        return $this->recurseArrayForTokens($arr, $tokens);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigFilePath()
    {
        return $this->configFilePath;
    }

    /**
     * Recurse an array for the specified tokens and replace them.
     * @param array $arr    Array to recurse
     * @param array $tokens Array of tokens to search for
     * @return array
     */
    protected function recurseArrayForTokens($arr, $tokens)
    {
        $out = [];
        foreach ($arr as $name => $value) {
            if (is_array($value)) {
                $out[$name] = $this->recurseArrayForTokens($value, $tokens);
                continue;
            }
            if (is_string($value)) {
                foreach ($tokens as $token => $tval) {
                    $value = str_replace($token, $tval, $value);
                }
                $out[$name] = $value;
                continue;
            }
            $out[$name] = $value;
        }

        return $out;
    }

    /**
     * Create a new instance of the config class using a PHP file path.
     * @param  string $configFilePath Path to the PHP File
     * @throws \RuntimeException
     * @return \Phinx\Config\Config
     */
    public static function fromPhp($configFilePath)
    {
        //ob_start();
        /** @noinspection PhpIncludeInspection */
        $configArray = include($configFilePath);

        //ob_end_clean();
        return new static($configArray, $configFilePath);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultEnvironment()
    {
        // The $PHINX_ENVIRONMENT variable overrides all other default settings
        $env = getenv('PHINX_ENVIRONMENT');
        if (!empty($env)) {
            if ($this->hasEnvironment($env)) {
                return $env;
            }
            throw new \RuntimeException(sprintf(
                'The environment configuration (read from $PHINX_ENVIRONMENT) for \'%s\' is missing',
                $env
            ));
        }
        // if the user has configured a default database then use it,
        // providing it actually exists!
        if (isset($this->values['environments']['default_database'])) {
            if ($this->getEnvironment($this->values['environments']['default_database'])) {
                return $this->values['environments']['default_database'];
            }
            throw new \RuntimeException(sprintf(
                'The environment configuration for \'%s\' is missing',
                $this->values['environments']['default_database']
            ));
        }
        // else default to the first available one
        if (is_array($this->getEnvironments()) && count($this->getEnvironments()) > 0) {
            $names = array_keys($this->getEnvironments());

            return $names[0];
        }
        throw new \RuntimeException('Could not find a default environment');
    }

    /**
     * {@inheritdoc}
     */
    public function hasEnvironment($name)
    {
        return ($this->getEnvironment($name) !== null);
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironment($name)
    {
        $environments = $this->getEnvironments();
        if (isset($environments[$name])) {
            if (isset($this->values['environments']['default_migration_table'])) {
                $environments[$name]['default_migration_table'] =
                    $this->values['environments']['default_migration_table'];
            }

            return $environments[$name];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironments()
    {
        if (isset($this->values) && isset($this->values['environments'])) {
            $environments = [];
            foreach ($this->values['environments'] as $key => $value) {
                if (is_array($value)) {
                    $environments[$key] = $value;
                }
            }

            return $environments;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias($alias)
    {
        return !empty($this->values['aliases'][$alias]) ? $this->values['aliases'][$alias] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationPaths()
    {
        if (!isset($this->values['paths']['migrations'])) {
            throw new \UnexpectedValueException('Migrations path missing from config file');
        }
        if (is_string($this->values['paths']['migrations'])) {
            $this->values['paths']['migrations'] = [$this->values['paths']['migrations']];
        }

        return $this->values['paths']['migrations'];
    }

    /**
     * Gets the base class name for migrations.
     * @param bool $dropNamespace Return the base migration class name without the namespace.
     * @return string
     */
    public function getMigrationBaseClassName($dropNamespace = true)
    {
        $className = !isset($this->values['migration_base_class']) ? 'Phinx\Migration\AbstractMigration' : $this->values['migration_base_class'];

        return $dropNamespace ? substr(strrchr($className, '\\'), 1) ?: $className : $className;
    }

    /**
     * {@inheritdoc}
     */
    public function getSeedPaths()
    {
        if (!isset($this->values['paths']['seeds'])) {
            throw new \UnexpectedValueException('Seeds path missing from config file');
        }
        if (is_string($this->values['paths']['seeds'])) {
            $this->values['paths']['seeds'] = [$this->values['paths']['seeds']];
        }

        return $this->values['paths']['seeds'];
    }

    /**
     * Get the template file name.
     * @return string|false
     */
    public function getTemplateFile()
    {
        if (!isset($this->values['templates']['file'])) {
            return false;
        }

        return $this->values['templates']['file'];
    }

    /**
     * Get the template class name.
     * @return string|false
     */
    public function getTemplateClass()
    {
        if (!isset($this->values['templates']['class'])) {
            return false;
        }

        return $this->values['templates']['class'];
    }

    /**
     * Is version order creation time?
     * @return bool
     */
    public function isVersionOrderCreationTime()
    {
        $versionOrder = $this->getVersionOrder();

        return $versionOrder == self::VERSION_ORDER_CREATION_TIME;
    }

    /**
     * Get the version order.
     * @return string
     */
    public function getVersionOrder()
    {
        if (!isset($this->values['version_order'])) {
            return self::VERSION_ORDER_CREATION_TIME;
        }

        return $this->values['version_order'];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($id, $value)
    {
        $this->values[$id] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($id)
    {
        if (!array_key_exists($id, $this->values)) {
            throw new \InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $id));
        }

        return $this->values[$id] instanceof \Closure ? $this->values[$id]($this) : $this->values[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($id)
    {
        return isset($this->values[$id]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($id)
    {
        unset($this->values[$id]);
    }
}
