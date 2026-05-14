<?php

namespace Sazl\LaravelRepokit;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
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

    protected function write(string $path, string $content): void
    {
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $content);
    }
}