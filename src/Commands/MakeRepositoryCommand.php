<?php

namespace Sazl\LaravelRepokit\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Sazl\LaravelRepokit\CommandGenerator;
use Sazl\LaravelRepokit\Utils\ConfigWriter;
use Sazl\LaravelRepokit\Utils\ConfigWriteResult;
use Sazl\LaravelRepokit\Utils\NameResolver;
use Sazl\LaravelRepokit\Utils\StubResolver;

class MakeRepositoryCommand extends CommandGenerator
{
    protected $signature = 'make:repository {name} {--M|model=} {--force : Overwrite existing generated files}';
    protected $description = 'Generate a new repository with an interface and register the binding in config/repository.php';


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
        $isforce = $this->option('force');

        $interfaceName = $this->resolver->repository($name, true);
        $repositoryName = $this->resolver->repository($name);

        $this->info("Generating repository [{$repositoryName}].");
        $this->line($model ? "Repository model: {$model}" : 'Repository type: Query Builder');

        // Render contract stub
        $interfaceContent = $this->stubResolver->render('repositories', 'contract', [
            '{{ interface }}' => $interfaceName,
        ]);

        // Render implementation stub
        $implVariant = $model ? 'implementation.model' : 'implementation';
        $serviceContent = $this->stubResolver->render('repositories', $implVariant, [
            '{{ interface }}' => $interfaceName,
            '{{ repository }}' => $repositoryName,
            '{{ modelFull }}' => $model,
            '{{ modelClass }}' => $model ? class_basename($model) : '',
            '{{ table }}' => Str::snake(Str::pluralStudly($name)),
        ]);

        // Write files
        $contractPath = $this->getTargetPath("Contracts/{$interfaceName}.php");
        $servicePath = $this->getTargetPath("Databases/{$repositoryName}.php");

        $this->write($contractPath, $interfaceContent, $isforce);
        $this->write($servicePath, $serviceContent, $isforce);

        // Register binding in config
        $interfaceFqcn = "App\\Repositories\\Contracts\\{$interfaceName}";
        $implementationFqcn = "App\\Repositories\\Databases\\{$repositoryName}";

        $configPath = config_path('repository.php');

        // Auto-publish config if it doesn't exist yet
        if (!file_exists($configPath)) {
            $this->call('vendor:publish', ['--tag' => 'repository-config']);
        }

        $result = $this->configWriter->addBinding($configPath, $interfaceFqcn, $implementationFqcn);

        match ($result) {
            ConfigWriteResult::SUCCESS => $this->info("Registered binding in config/repository.php: {$interfaceFqcn} => {$implementationFqcn}."),
            ConfigWriteResult::ALREADY_EXISTS => $this->info("Binding for {$interfaceFqcn} already exists in config/repository.php; left unchanged."),
            ConfigWriteResult::FILE_NOT_FOUND => $this->error("Config file not found. Please publish it using: php artisan vendor:publish --tag=repository-config"),
            ConfigWriteResult::NOT_WRITABLE => $this->error("Config file config/repository.php is not writable."),
        };
    }


    protected function getTargetPath(string $name): string
    {
        return app_path("Repositories/{$name}");
    }
}
