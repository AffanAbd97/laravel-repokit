<?php

namespace Sazl\LaravelRepokit\Tests;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;

class MakeRepositoryCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        File::ensureDirectoryExists(config_path());
        File::put(config_path('repository.php'), "<?php return ['bindings' => []];");
        File::put(config_path('service.php'), "<?php return ['bindings' => []];");
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(app_path('Repositories'));
        File::deleteDirectory(app_path('Services'));
        File::delete([config_path('repository.php'), config_path('service.php')]);

        parent::tearDown();
    }

    public function test_make_repository_command_is_registered(): void
    {
        $commands = $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->all();
        $this->assertArrayHasKey('make:repository', $commands);
    }

    public function test_make_service_command_is_registered(): void
    {
        $commands = $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->all();
        $this->assertArrayHasKey('make:service', $commands);
    }

    public static function generatorOutputProvider(): array
    {
        return [
            'repository' => [
                'make:repository', [],
                ['Generating repository [UserRepository].', 'Repository type: Query Builder'],
                ['Repositories/Contracts/UserRepositoryInterface.php', 'Repositories/Databases/UserRepository.php'],
                'App\\Repositories\\Contracts\\UserRepositoryInterface => App\\Repositories\\Databases\\UserRepository',
            ],
            'model repository' => [
                'make:repository', ['--model' => 'App\\Custom\\User'],
                ['Generating repository [UserRepository].', 'Repository model: App\\Custom\\User'],
                ['Repositories/Contracts/UserRepositoryInterface.php', 'Repositories/Databases/UserRepository.php'],
                'App\\Repositories\\Contracts\\UserRepositoryInterface => App\\Repositories\\Databases\\UserRepository',
            ],
            'service' => [
                'make:service', ['--repository' => 'Account'],
                ['Generating service [UserService].', 'Repository interface: App\\Repositories\\Contracts\\AccountRepositoryInterface'],
                ['Services/Contracts/UserServiceInterface.php', 'Services/UserService.php'],
                'App\\Services\\Contracts\\UserServiceInterface => App\\Services\\UserService',
            ],
            'empty service' => [
                'make:service', ['--empty' => true],
                ['Generating service [UserService].', 'Repository interface: App\\Repositories\\Contracts\\UserRepositoryInterface', 'Template: empty service (no pre-built methods)'],
                ['Services/Contracts/UserServiceInterface.php', 'Services/UserService.php'],
                'App\\Services\\Contracts\\UserServiceInterface => App\\Services\\UserService',
            ],
        ];
    }

    #[DataProvider('generatorOutputProvider')]
    public function test_generator_reports_created_and_overwritten_files(
        string $command,
        array $options,
        array $context,
        array $paths,
        string $binding
    ): void {
        // --force still reports "Created" when the files are new.
        $parameters = ['name' => 'User', '--force' => true] + $options;
        $pending = $this->artisan($command, $parameters);
        foreach ($context as $line) {
            $pending->expectsOutput($line);
        }
        foreach ($paths as $path) {
            $pending->expectsOutput('Created: ' . app_path($path));
        }
        $pending->expectsOutputToContain($binding)->assertSuccessful()->run();

        foreach ($paths as $path) {
            $this->assertFileExists(app_path($path));
            File::put(app_path($path), 'Manual changes');
        }

        $pending = $this->artisan($command, $parameters);
        foreach ($paths as $path) {
            $pending->expectsOutput('Overwritten: ' . app_path($path));
        }
        $pending->expectsOutputToContain('left unchanged')->assertSuccessful()->run();

        foreach ($paths as $path) {
            $this->assertNotSame('Manual changes', File::get(app_path($path)));
        }
    }
}
