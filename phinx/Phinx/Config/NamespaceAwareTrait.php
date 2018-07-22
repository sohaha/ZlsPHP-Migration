<?php

namespace Phinx\Config;

/**
 * Trait implemented NamespaceAwareInterface.
 * @package Phinx\Config
 * @author  Andrey N. Mokhov
 */
trait NamespaceAwareTrait
{
    /**
     * Gets the paths to search for migration files.
     * @return string[]
     */
    abstract public function getMigrationPaths();

    /**
     * Gets the paths to search for seed files.
     * @return string[]
     */
    abstract public function getSeedPaths();

    /**
     * Get Migration Namespace associated with path.
     * @param string $path
     * @return string|null
     */
    public function getMigrationNamespaceByPath($path)
    {
        $paths = $this->getMigrationPaths();

        return $this->searchNamespace($path, $paths);
    }

    /**
     * Search $needle in $haystack and return key associate with him.
     * @param string $needle
     * @param array  $haystack
     * @return null|string
     */
    protected function searchNamespace($needle, $haystack)
    {
        $needle = realpath($needle);
        $haystack = array_map('realpath', $haystack);
        $key = array_search($needle, $haystack);

        return is_string($key) ? trim($key, '\\') : null;
    }

    /**
     * Get Seed Namespace associated with path.
     * @param string $path
     * @return string|null
     */
    public function getSeedNamespaceByPath($path)
    {
        $paths = $this->getSeedPaths();

        return $this->searchNamespace($path, $paths);
    }
}
