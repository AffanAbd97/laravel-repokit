<?php

namespace Sazl\LaravelRepokit\Tests;

use Sazl\LaravelRepokit\Providers\RepokitProvider;

class RepokitProviderTest extends TestCase
{
    public function test_publishes_source_path_resolves_to_src_stubs_directory(): void
    {
        $publishableGroups = RepokitProvider::$publishGroups ?? [];

        // Get all paths registered under the 'repokit-stubs' tag
        $paths = RepokitProvider::pathsToPublish(RepokitProvider::class, 'repokit-stubs');

        $this->assertNotEmpty($paths, 'No publishable paths registered under repokit-stubs tag');

        $sourcePath = array_key_first($paths);
        $expectedSourcePath = realpath(__DIR__ . '/../src/stubs');

        $this->assertNotFalse($expectedSourcePath, 'src/stubs directory does not exist');
        $this->assertEquals(
            $expectedSourcePath,
            realpath($sourcePath),
            'The registered source path should resolve to the src/stubs/ directory'
        );
    }

    public function test_publishes_destination_path_is_resource_stubs_vendor_repokit(): void
    {
        $paths = RepokitProvider::pathsToPublish(RepokitProvider::class, 'repokit-stubs');

        $this->assertNotEmpty($paths, 'No publishable paths registered under repokit-stubs tag');

        $destinationPath = array_values($paths)[0];
        $expectedDestination = resource_path('stubs/vendor/repokit');

        $this->assertEquals(
            $expectedDestination,
            $destinationPath,
            'The registered destination path should be resource_path(\'stubs/vendor/repokit\')'
        );
    }

    public function test_publish_tag_is_repokit_stubs(): void
    {
        $paths = RepokitProvider::pathsToPublish(RepokitProvider::class, 'repokit-stubs');

        $this->assertNotEmpty(
            $paths,
            'The provider should register publishable paths under the \'repokit-stubs\' tag'
        );
    }
}
