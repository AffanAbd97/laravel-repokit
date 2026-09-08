<?php

namespace Sazl\LaravelRepokit\Tests;

use Illuminate\Support\Facades\File;

class MakeModuleCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure config files exist with bindings array for testing
        $repoConfigPath = config_path('repository.php');
        $serviceConfigPath = config_path('service.php');

        File::ensureDirectoryExists(dirname($repoConfigPath));
        File::ensureDirectoryExists(dirname($serviceConfigPath));

        File::put($repoConfigPath, "<?php\n\nreturn [\n    'bindings' => [\n    ],\n];\n");
        File::put($serviceConfigPath, "<?php\n\nreturn [\n    'bindings' => [\n    ],\n];\n");
    }

    protected function tearDown(): void
    {
        // Clean up generated files
        File::deleteDirectory(app_path('Repositories'));
        File::deleteDirectory(app_path('Services'));

        $repoConfigPath = config_path('repository.php');
        $serviceConfigPath = config_path('service.php');

        if (File::exists($repoConfigPath)) {
            File::delete($repoConfigPath);
        }
        if (File::exists($serviceConfigPath)) {
            File::delete($serviceConfigPath);
        }

        parent::tearDown();
    }

    // 7.1 - Test command registration
    public function test_make_module_command_is_registered(): void
    {
        $commands = $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->all();
        $this->assertArrayHasKey('make:module', $commands);
    }

    // 7.2 - Test file generation without model
    public function test_make_module_generates_four_files_without_model(): void
    {
        $this->artisan('make:module', ['name' => 'User'])
            ->assertSuccessful();

        $this->assertFileExists(app_path('Repositories/Contracts/UserRepositoryInterface.php'));
        $this->assertFileExists(app_path('Repositories/Databases/UserRepository.php'));
        $this->assertFileExists(app_path('Services/Contracts/UserServiceInterface.php'));
        $this->assertFileExists(app_path('Services/UserService.php'));
    }

    // 7.3 - Test file generation with model
    public function test_make_module_with_model_uses_model_stub(): void
    {
        $this->artisan('make:module', ['name' => 'User', '--model' => 'User'])
            ->assertSuccessful();

        $repoContent = File::get(app_path('Repositories/Databases/UserRepository.php'));

        // The model stub should contain the model class name and use statement
        $this->assertStringContainsString('use App\Models\User;', $repoContent);
        $this->assertStringContainsString('User $model', $repoContent);
    }

    // 7.4 - Test model FQCN resolution
    public function test_model_without_namespace_gets_app_models_prepended(): void
    {
        $this->artisan('make:module', ['name' => 'User', '--model' => 'User'])
            ->assertSuccessful();

        $repoContent = File::get(app_path('Repositories/Databases/UserRepository.php'));
        $this->assertStringContainsString('use App\Models\User;', $repoContent);
    }

    public function test_model_with_full_namespace_stays_unchanged(): void
    {
        $this->artisan('make:module', ['name' => 'User', '--model' => 'App\Custom\User'])
            ->assertSuccessful();

        $repoContent = File::get(app_path('Repositories/Databases/UserRepository.php'));
        $this->assertStringContainsString('use App\Custom\User;', $repoContent);
    }

    // 7.5 - Test config binding registration
    public function test_config_bindings_are_registered_after_command(): void
    {
        $this->artisan('make:module', ['name' => 'User'])
            ->assertSuccessful();

        $repoConfig = File::get(config_path('repository.php'));
        $serviceConfig = File::get(config_path('service.php'));

        // Repository binding
        $this->assertStringContainsString('App\Repositories\Contracts\UserRepositoryInterface', $repoConfig);
        $this->assertStringContainsString('App\Repositories\Databases\UserRepository', $repoConfig);

        // Service binding
        $this->assertStringContainsString('App\Services\Contracts\UserServiceInterface', $serviceConfig);
        $this->assertStringContainsString('App\Services\UserService', $serviceConfig);
    }

    // 7.6 - Test duplicate binding handling
    public function test_duplicate_binding_shows_already_exists_message(): void
    {
        // First run
        $this->artisan('make:module', ['name' => 'User'])
            ->assertSuccessful();

        // Second run should report "already exists"
        $this->artisan('make:module', ['name' => 'User'])
            ->expectsOutputToContain('already exists')
            ->assertSuccessful();
    }

    public function test_duplicate_binding_does_not_create_duplicate_entries(): void
    {
        // First run
        $this->artisan('make:module', ['name' => 'User'])
            ->assertSuccessful();

        // Second run
        $this->artisan('make:module', ['name' => 'User'])
            ->assertSuccessful();

        $repoConfig = File::get(config_path('repository.php'));
        $serviceConfig = File::get(config_path('service.php'));

        // Count occurrences - should only appear once each
        $this->assertEquals(
            1,
            substr_count($repoConfig, 'UserRepositoryInterface')
        );
        $this->assertEquals(
            1,
            substr_count($serviceConfig, 'UserServiceInterface')
        );
    }
}
