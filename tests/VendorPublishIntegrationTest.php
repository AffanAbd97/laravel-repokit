<?php

namespace Sazl\LaravelRepokit\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class VendorPublishIntegrationTest extends TestCase
{
    private string $publishedPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publishedPath = resource_path('stubs/vendor/repokit');
    }

    protected function tearDown(): void
    {
        // Clean up published stubs after each test
        if (File::isDirectory($this->publishedPath)) {
            File::deleteDirectory($this->publishedPath);
        }

        parent::tearDown();
    }

    public function test_vendor_publish_repokit_stubs_copies_stubs_correctly(): void
    {
        // Ensure destination doesn't exist before publishing
        $this->assertDirectoryDoesNotExist($this->publishedPath);

        // Run vendor:publish with the repokit-stubs tag
        $exitCode = Artisan::call('vendor:publish', ['--tag' => 'repokit-stubs']);

        $this->assertEquals(0, $exitCode, 'vendor:publish command should exit with code 0');

        // Assert the destination directory was created
        $this->assertDirectoryExists($this->publishedPath);

        // Assert both repositories/ and services/ subdirectories exist
        $this->assertDirectoryExists(
            $this->publishedPath . '/repositories',
            'The repositories/ subdirectory should be created at the publish destination'
        );
        $this->assertDirectoryExists(
            $this->publishedPath . '/services',
            'The services/ subdirectory should be created at the publish destination'
        );

        // Assert all expected stub files are present
        $expectedFiles = [
            'repositories/contract.stub',
            'repositories/implementation.model.stub',
            'repositories/implementation.stub',
            'services/contract.empty.stub',
            'services/contract.stub',
            'services/implementation.empty.stub',
            'services/implementation.stub',
        ];

        foreach ($expectedFiles as $file) {
            $this->assertFileExists(
                $this->publishedPath . '/' . $file,
                "Expected stub file '{$file}' should be present at the publish destination"
            );
        }
    }
}
