<?php

namespace Sazl\LaravelRepokit\Tests;

use Illuminate\Filesystem\Filesystem;
use Sazl\LaravelRepokit\Utils\StubResolver;

class StubResolverTest extends TestCase
{
    protected Filesystem $files;

    protected StubResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->files = new Filesystem();
        $this->resolver = new StubResolver($this->files);
    }

    protected function tearDown(): void
    {
        // Clean up any published stubs created during tests
        $publishedPath = resource_path('stubs/vendor/repokit');
        if (is_dir($publishedPath)) {
            $this->cleanDirectory($publishedPath);
        }

        parent::tearDown();
    }

    /**
     * Recursively remove a directory and its contents safely.
     */
    private function cleanDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = @scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $fullPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) {
                $this->cleanDirectory($fullPath);
            } else {
                @unlink($fullPath);
            }
        }
        @rmdir($path);
    }

    /**
     * Known layer/variant combinations from src/stubs/.
     */
    public static function knownLayerVariantProvider(): array
    {
        return [
            'repositories/contract' => ['repositories', 'contract'],
            'repositories/implementation.model' => ['repositories', 'implementation.model'],
            'repositories/implementation' => ['repositories', 'implementation'],
            'services/contract.empty' => ['services', 'contract.empty'],
            'services/contract' => ['services', 'contract'],
            'services/implementation.empty' => ['services', 'implementation.empty'],
            'services/implementation' => ['services', 'implementation'],
        ];
    }

    /**
     * Generate random layer/variant combinations to reach 100+ iterations.
     */
    public static function randomLayerVariantProvider(): array
    {
        $cases = [];
        $layers = ['models', 'controllers', 'middleware', 'events', 'listeners', 'jobs', 'policies'];
        $variants = ['default', 'custom', 'extended', 'minimal', 'full', 'api', 'web'];

        // Generate enough random combinations to reach 100+ total iterations
        // (7 known + 93 random = 100)
        for ($i = 0; $i < 93; $i++) {
            $layer = $layers[array_rand($layers)] . '_' . $i;
            $variant = $variants[array_rand($variants)] . '_' . $i;
            $cases["random_{$i}_{$layer}/{$variant}"] = [$layer, $variant];
        }

        return $cases;
    }

    /**
     * Combined provider for all layer/variant combinations (known + random).
     */
    public static function allLayerVariantProvider(): array
    {
        return array_merge(
            static::knownLayerVariantProvider(),
            static::randomLayerVariantProvider()
        );
    }

    /**
     * Property 1: Published stub takes priority (known combinations)
     *
     * For all known layer/variant combinations in src/stubs/, create a
     * published stub at the published path, then assert StubResolver::resolve()
     * returns the published path.
     *
     * **Validates: Requirements 2.3, 4.1**
     *
     * @dataProvider knownLayerVariantProvider
     */
    public function test_published_stub_takes_priority_known(string $layer, string $variant): void
    {
        $publishedDir = resource_path("stubs/vendor/repokit/{$layer}");
        $publishedPath = "{$publishedDir}/{$variant}.stub";

        // Create a published stub
        $this->files->ensureDirectoryExists($publishedDir);
        $this->files->put($publishedPath, "custom published stub for {$layer}/{$variant}");

        // Assert StubResolver returns the published path
        $resolved = $this->resolver->resolve($layer, $variant);
        $this->assertEquals(
            $publishedPath,
            $resolved,
            "Published stub should take priority for {$layer}/{$variant}"
        );
    }

    /**
     * Property 1: Published stub takes priority (random combinations)
     *
     * For randomly generated layer/variant names, create both a package stub
     * and a published stub, then assert StubResolver::resolve() returns the
     * published path.
     *
     * **Validates: Requirements 2.3, 4.1**
     *
     * @dataProvider randomLayerVariantProvider
     */
    public function test_published_stub_takes_priority_random(string $layer, string $variant): void
    {
        $publishedDir = resource_path("stubs/vendor/repokit/{$layer}");
        $publishedPath = "{$publishedDir}/{$variant}.stub";
        $packageDir = $this->resolver->getPackagePath() . "/{$layer}";
        $packagePath = "{$packageDir}/{$variant}.stub";

        // For random names, create a package stub first
        $this->files->ensureDirectoryExists($packageDir);
        $this->files->put($packagePath, "package stub content for {$layer}/{$variant}");

        try {
            // Create a published stub
            $this->files->ensureDirectoryExists($publishedDir);
            $this->files->put($publishedPath, "custom published stub for {$layer}/{$variant}");

            // Assert StubResolver returns the published path
            $resolved = $this->resolver->resolve($layer, $variant);
            $this->assertEquals(
                $publishedPath,
                $resolved,
                "Published stub should take priority for random {$layer}/{$variant}"
            );
        } finally {
            // Clean up the package stub we created for this random test
            @unlink($packagePath);
            if (is_dir($packageDir) && count(@scandir($packageDir) ?: []) <= 2) {
                @rmdir($packageDir);
            }
        }
    }

    /**
     * Property 2: Resolution round-trip fallback
     *
     * For all known layer/variant combinations, create a published stub,
     * verify it resolves to published path, delete it, verify it resolves
     * back to package path.
     *
     * **Validates: Requirements 4.2, 4.3**
     *
     * @dataProvider knownLayerVariantProvider
     */
    public function test_resolution_round_trip_fallback_known(string $layer, string $variant): void
    {
        $publishedDir = resource_path("stubs/vendor/repokit/{$layer}");
        $publishedPath = "{$publishedDir}/{$variant}.stub";
        $packagePath = $this->resolver->getPackagePath() . "/{$layer}/{$variant}.stub";

        // Precondition: package stub must exist for known combinations
        $this->assertFileExists($packagePath, "Package stub should exist for known combination {$layer}/{$variant}");

        // Step 1: Create a published stub
        $this->files->ensureDirectoryExists($publishedDir);
        $this->files->put($publishedPath, "published stub content for {$layer}/{$variant}");

        // Step 2: Assert StubResolver returns the published path
        $resolved = $this->resolver->resolve($layer, $variant);
        $this->assertEquals(
            $publishedPath,
            $resolved,
            "Published stub should take priority for {$layer}/{$variant}"
        );

        // Step 3: Delete the published stub
        $this->files->delete($publishedPath);

        // Step 4: Assert StubResolver falls back to package path
        $resolved = $this->resolver->resolve($layer, $variant);
        $this->assertEquals(
            $packagePath,
            $resolved,
            "After deleting published stub, should fall back to package path for {$layer}/{$variant}"
        );
    }

    /**
     * Property 2: Resolution round-trip fallback (random combinations)
     *
     * For randomly generated layer/variant names, create both a package stub
     * and a published stub, verify resolution priority, delete published,
     * verify fallback to package stub.
     *
     * **Validates: Requirements 4.2, 4.3**
     *
     * @dataProvider randomLayerVariantProvider
     */
    public function test_resolution_round_trip_fallback_random(string $layer, string $variant): void
    {
        $publishedDir = resource_path("stubs/vendor/repokit/{$layer}");
        $publishedPath = "{$publishedDir}/{$variant}.stub";
        $packageDir = $this->resolver->getPackagePath() . "/{$layer}";
        $packagePath = "{$packageDir}/{$variant}.stub";

        // For random names, we need to create a package stub too
        $this->files->ensureDirectoryExists($packageDir);
        $this->files->put($packagePath, "package stub content for {$layer}/{$variant}");

        try {
            // Step 1: Create a published stub
            $this->files->ensureDirectoryExists($publishedDir);
            $this->files->put($publishedPath, "published stub content for {$layer}/{$variant}");

            // Step 2: Assert StubResolver returns the published path
            $resolved = $this->resolver->resolve($layer, $variant);
            $this->assertEquals(
                $publishedPath,
                $resolved,
                "Published stub should take priority for random {$layer}/{$variant}"
            );

            // Step 3: Delete the published stub
            $this->files->delete($publishedPath);

            // Step 4: Assert StubResolver falls back to package path
            $resolved = $this->resolver->resolve($layer, $variant);
            $this->assertEquals(
                $packagePath,
                $resolved,
                "After deleting published stub, should fall back to package path for random {$layer}/{$variant}"
            );
        } finally {
            // Clean up the package stub we created for this random test
            @unlink($packagePath);
            if (is_dir($packageDir) && count(@scandir($packageDir) ?: []) <= 2) {
                @rmdir($packageDir);
            }
        }
    }
}
