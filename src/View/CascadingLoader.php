<?php

namespace MCAG\View;

use Mustache_Loader;
use Mustache_Loader_FilesystemLoader;
use Mustache_Exception_UnknownTemplateException;
use Exception;

/**
 * Mustache Cascading Loader
 * Allows templates to be loaded from multiple directories
 */
class CascadingLoader implements Mustache_Loader
{
    private array $loaders = [];

    public function __construct(array $directories)
    {
        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $this->loaders[] = new Mustache_Loader_FilesystemLoader($dir);
            }
        }
    }

    public function load($name)
    {
        foreach ($this->loaders as $loader) {
            try {
                return $loader->load($name);
            } catch (Exception $e) {
                // Try next loader
                continue;
            }
        }

        throw new Mustache_Exception_UnknownTemplateException($name);
    }
}


