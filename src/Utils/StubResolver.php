<?php

namespace Sazl\LaravelRepokit\Utils;

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;

class StubResolver
{
    protected Filesystem $files;

    protected string $packageStubPath;

    protected string $publishedStubPath;

    public function __construct(?Filesystem $files = null)
    {
        $this->files = $files ?? new Filesystem();
        $this->packageStubPath = __DIR__ . '/../stubs';
        $this->publishedStubPath = resource_path('stubs/vendor/repokit');
    }


    public function resolve(string $layer, string $variant): string
    {
        $relativePath = "$layer/$variant.stub";

        $publishedPath = $this->publishedStubPath . '/' . $relativePath;
        if ($this->files->exists($publishedPath)) {
            return $publishedPath;
        }

        $packagePath = $this->packageStubPath . '/' . $relativePath;
        if ($this->files->exists($packagePath)) {
            return $packagePath;
        }

        throw new InvalidArgumentException(
            "Stub not found: [$relativePath]. Looked in:\n  - $publishedPath\n  - $packagePath"
        );
    }


    public function render(string $layer, string $variant, array $replacements = []): string
    {
        $stubPath = $this->resolve($layer, $variant);
        $content = $this->files->get($stubPath);

        foreach ($replacements as $placeholder => $value) {
            $content = str_replace($placeholder, $value ?? '', $content);
        }

        return $content;
    }

    public function variants(string $layer): array
    {
        $variants = [];

        $packageDir = $this->packageStubPath . '/' . $layer;
        if ($this->files->isDirectory($packageDir)) {
            foreach ($this->files->files($packageDir) as $file) {
                $variants[] = str_replace('.stub', '', $file->getFilename());
            }
        }

        $publishedDir = $this->publishedStubPath . '/' . $layer;
        if ($this->files->isDirectory($publishedDir)) {
            foreach ($this->files->files($publishedDir) as $file) {
                $variant = str_replace('.stub', '', $file->getFilename());
                if (!in_array($variant, $variants)) {
                    $variants[] = $variant;
                }
            }
        }

        sort($variants);
        return $variants;
    }

    public function exists(string $layer, string $variant): bool
    {
        $relativePath = "$layer/$variant.stub";

        return $this->files->exists($this->publishedStubPath . '/' . $relativePath)
            || $this->files->exists($this->packageStubPath . '/' . $relativePath);
    }


    public function getPackagePath(): string
    {
        return $this->packageStubPath;
    }


    public function getPublishedPath(): string
    {
        return $this->publishedStubPath;
    }
}
