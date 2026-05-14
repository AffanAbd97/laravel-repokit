<?php

namespace Sazl\LaravelRepokit\Utils;

use Illuminate\Filesystem\Filesystem;

class ConfigWriter
{
    public function __construct(
        protected Filesystem $files
    ) {
    }

    /**
     * Add a binding entry to the config file.
     *
     * @param string $configPath Absolute path to config/service.php
     * @param string $interface  FQCN of the interface
     * @param string $implementation FQCN of the implementation
     * @return ConfigWriteResult
     */
    public function addBinding(string $configPath, string $interface, string $implementation): ConfigWriteResult
    {
        if (!file_exists($configPath)) {
            return ConfigWriteResult::FILE_NOT_FOUND;
        }

        if (!is_writable($configPath)) {
            return ConfigWriteResult::NOT_WRITABLE;
        }

        $config = include $configPath;

        $bindings = $config['bindings'] ?? [];

        if (array_key_exists($interface, $bindings)) {
            return ConfigWriteResult::ALREADY_EXISTS;
        }

        $bindings[$interface] = $implementation;
        $config['bindings'] = $bindings;

        $content = $this->buildConfigContent($config);

        $this->files->put($configPath, $content);

        return ConfigWriteResult::SUCCESS;
    }

    /**
     * Build a clean PHP config file content from the given array.
     */
    protected function buildConfigContent(array $config): string
    {
        $bindings = $config['bindings'] ?? [];

        $lines = "<?php\n\nreturn [\n    'bindings' => [\n";

        foreach ($bindings as $interface => $implementation) {
            $lines .= "        {$this->formatClassReference($interface)} => {$this->formatClassReference($implementation)},\n";
        }

        $lines .= "    ],\n];\n";

        return $lines;
    }

    /**
     * Format a class name as a ::class reference.
     */
    protected function formatClassReference(string $className): string
    {
        return '\\' . ltrim($className, '\\') . '::class';
    }
}
