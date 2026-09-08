<?php

namespace Sazl\LaravelRepokit\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Sazl\LaravelRepokit\CommandGenerator;
use Sazl\LaravelRepokit\Utils\ConfigWriter;
use Sazl\LaravelRepokit\Utils\ConfigWriteResult;
use Sazl\LaravelRepokit\Utils\NameResolver;
use Sazl\LaravelRepokit\Utils\StubResolver;

class MakeModuleCommand extends CommandGenerator
{
    protected $signature = 'make:module {name} {--M|model=} {--force : Overwrite existing generated files}';
    protected $description = 'Generate a new repository and service module with interfaces and auto-bind them';

    protected ConfigWriter $configWriter;

    public function __construct(Filesystem $filesystem, NameResolver $resolver, StubResolver $stubResolver, ConfigWriter $configWriter)
    {
        parent::__construct($filesystem, $resolver, $stubResolver);
        $this->configWriter = $configWriter;
    }

    public function handle()
    {
        $name = $this->argument('name');
        $modelInput = $this->option('model');
        $model = $modelInput ? (str_contains($modelInput, '\\') ? $modelInput : "App\\Models\\$modelInput") : null;
        $isForce = $this->option('force');

        $this->info("Generating module [{$name}].");
        $this->line($model ? "Repository model: {$model}" : 'Repository type: Query Builder');

        // --- Repository Generation ---

        $repoInterfaceName = $this->resolver->repository($name, true);
        $repositoryName = $this->resolver->repository($name);

        // Render repository contract stub
        $repoInterfaceContent = $this->stubResolver->render('repositories', 'contract', [
            '{{ interface }}' => $repoInterfaceName,
        ]);

        // Render repository implementation stub
        $repoImplVariant = $model ? 'implementation.model' : 'implementation';
        $repoContent = $this->stubResolver->render('repositories', $repoImplVariant, [
            '{{ interface }}' => $repoInterfaceName,
            '{{ repository }}' => $repositoryName,
            '{{ modelFull }}' => $model,
            '{{ modelClass }}' => $model ? class_basename($model) : '',
            '{{ table }}' => Str::snake(Str::pluralStudly($name)),
        ]);

        // Write repository files
        $repoContractPath = app_path("Repositories/Contracts/{$repoInterfaceName}.php");
        $repoImplPath = app_path("Repositories/Databases/{$repositoryName}.php");

        $this->write($repoContractPath, $repoInterfaceContent, $isForce);
        $this->write($repoImplPath, $repoContent, $isForce);

        // --- Service Generation ---

        $serviceInterfaceName = $this->resolver->service($name, true);
        $serviceName = $this->resolver->service($name);
        $repositoryInterface = $this->resolver->repository($name, true);

        // Render service contract stub
        $serviceInterfaceContent = $this->stubResolver->render('services', 'contract', [
            '{{ interface }}' => $serviceInterfaceName,
        ]);

        // Render service implementation stub (non-empty variant)
        $serviceContent = $this->stubResolver->render('services', 'implementation', [
            '{{ service_interface }}' => $serviceInterfaceName,
            '{{ service }}' => $serviceName,
            '{{ repository_interface }}' => $repositoryInterface,
        ]);

        // Write service files
        $serviceContractPath = app_path("Services/Contracts/{$serviceInterfaceName}.php");
        $serviceImplPath = app_path("Services/{$serviceName}.php");

        $this->write($serviceContractPath, $serviceInterfaceContent, $isForce);
        $this->write($serviceImplPath, $serviceContent, $isForce);

        // --- Config Binding Registration ---

        // Repository binding
        $repoInterfaceFqcn = "App\\Repositories\\Contracts\\{$repoInterfaceName}";
        $repoImplementationFqcn = "App\\Repositories\\Databases\\{$repositoryName}";

        $repoConfigPath = config_path('repository.php');

        if (!file_exists($repoConfigPath)) {
            $this->call('vendor:publish', ['--tag' => 'repository-config']);
        }

        $repoResult = $this->configWriter->addBinding($repoConfigPath, $repoInterfaceFqcn, $repoImplementationFqcn);

        match ($repoResult) {
            ConfigWriteResult::SUCCESS => $this->info("Registered binding in config/repository.php: {$repoInterfaceFqcn} => {$repoImplementationFqcn}."),
            ConfigWriteResult::ALREADY_EXISTS => $this->info("Binding for {$repoInterfaceFqcn} already exists in config/repository.php; left unchanged."),
            ConfigWriteResult::FILE_NOT_FOUND => $this->error("Config file not found. Please publish it using: php artisan vendor:publish --tag=repository-config"),
            ConfigWriteResult::NOT_WRITABLE => $this->error("Config file config/repository.php is not writable."),
        };

        // Service binding
        $serviceInterfaceFqcn = "App\\Services\\Contracts\\{$serviceInterfaceName}";
        $serviceImplementationFqcn = "App\\Services\\{$serviceName}";

        $serviceConfigPath = config_path('service.php');

        if (!file_exists($serviceConfigPath)) {
            $this->call('vendor:publish', ['--tag' => 'service-config']);
        }

        $serviceResult = $this->configWriter->addBinding($serviceConfigPath, $serviceInterfaceFqcn, $serviceImplementationFqcn);

        match ($serviceResult) {
            ConfigWriteResult::SUCCESS => $this->info("Registered binding in config/service.php: {$serviceInterfaceFqcn} => {$serviceImplementationFqcn}."),
            ConfigWriteResult::ALREADY_EXISTS => $this->info("Binding for {$serviceInterfaceFqcn} already exists in config/service.php; left unchanged."),
            ConfigWriteResult::FILE_NOT_FOUND => $this->error("Config file not found. Please publish it using: php artisan vendor:publish --tag=service-config"),
            ConfigWriteResult::NOT_WRITABLE => $this->error("Config file config/service.php is not writable."),
        };
    }

    protected function getTargetPath(string $name): string
    {
        return app_path($name);
    }
}
