<?php

namespace Phinx\Config;

/**
 * Config aware getNamespaceByPath method.
 * @package Phinx\Config
 * @author  Andrey N. Mokhov
 */
interface NamespaceAwareInterface
{
    /**
     * Get Migration Namespace associated with path.
     * @param string $path
     * @return string|null
     */
    public function getMigrationNamespaceByPath($path);

    /**
     * Get Seed Namespace associated with path.
     * @param string $path
     * @return string|null
     */
    public function getSeedNamespaceByPath($path);
}
