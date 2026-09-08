<?php

namespace Sazl\LaravelRepokit;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Sazl\LaravelRepokit\Utils\NameResolver;
use Sazl\LaravelRepokit\Utils\StubResolver;



abstract class CommandGenerator extends Command
{

    protected Filesystem $files;
    protected NameResolver $resolver;

    protected StubResolver $stubResolver;

    public function __construct(Filesystem $filesystem, NameResolver $resolver, StubResolver $stubResolver)
    {
        parent::__construct();
        $this->files = $filesystem;
        $this->resolver = $resolver;
        $this->stubResolver = $stubResolver;
    }
    abstract protected function getTargetPath(string $name): string;

    protected function build(array $replacements, string $stub): string
    {
        $content = $this->files->get($stub);

        foreach ($replacements as $key => $value) {
            $content = str_replace($key, $value ?? '', $content);
        }

        return $content;
    }

    protected function write(string $path, string $content, bool $force = false): void
    {
        $exists = $this->files->exists($path);

        if ($exists && !$force) {
            throw new RuntimeException(
                "File already exists: {$path}\nChoose a different name, or rerun with --force to overwrite existing files. Manual changes will be lost."
            );
        }
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $content);

        $this->info(($exists ? 'Overwritten: ' : 'Created: ') . $path);
    }
}
