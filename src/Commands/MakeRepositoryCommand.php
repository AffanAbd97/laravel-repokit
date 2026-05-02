<?php

namespace Sazl\LaravelRepokit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Sazl\LaravelRepokit\Utils\NameResolver;

class MakeRepositoryCommand extends Command
{
    protected $signature = 'make:repository {name} {--M|model=}';
    protected $description = 'Generate a new repository with an interface and auto-bind it in AppServiceProvider';

    public function handle()
    {
        $nameResolver = new NameResolver();
        $name = $this->argument('name');
        $modelInput = $this->option('model');
        $model = $modelInput ? (str_contains($modelInput, '\\') ? $modelInput : "App\\Models\\$modelInput") : null;

        $interfaceName = $nameResolver->repository($name, true);
        $repositoryName = $nameResolver->repository($name);

        $filesystem = new Filesystem();
        $stubPath = __DIR__ . '/../stubs';

        $interfaceTemplate = file_get_contents("$stubPath/repository.contract.stub");
        $interfaceContent = str_replace('{{ interface }}', $interfaceName, $interfaceTemplate);

        $repositoryStub = $model ? 'repository.model.stub' : 'repository.stub';
        $repositoryTemplate = file_get_contents("$stubPath/$repositoryStub");

        $replacements = [
            '{{ interface }}' => $interfaceName,
            '{{ repository }}' => $repositoryName,
            '{{ modelFull }}' => $model,
            '{{ modelClass }}' => $model ? class_basename($model) : '',
            '{{ table }}' => Str::snake(Str::pluralStudly($name)),
        ];

        foreach ($replacements as $key => $value) {
            $repositoryTemplate = str_replace($key, $value, $repositoryTemplate);
        }

        $filesystem->ensureDirectoryExists(app_path('Repositories/Contracts'));
        $filesystem->ensureDirectoryExists(app_path('Repositories/Databases'));

        $filesystem->put(app_path("Repositories/Contracts/{$interfaceName}.php"), $interfaceContent);
        $filesystem->put(app_path("Repositories/Databases/{$repositoryName}.php"), $repositoryTemplate);

        $this->addBindingToServiceProvider($interfaceName, $repositoryName);
        $this->info("✅ Repository and interface created successfully!");
    }

    protected function addBindingToServiceProvider($interface, $repository)
    {
        $providerPath = app_path('Providers/AppServiceProvider.php');
        if (!file_exists($providerPath)) {
            $this->error("⚠️ AppServiceProvider.php not found!");
            return;
        }

        $content = file_get_contents($providerPath);
        $binding = "        \$this->app->bind(\\App\\Repositories\\Contracts\\$interface::class, \\App\\Repositories\\Databases\\$repository::class);\n";

        if (strpos($content, $binding) !== false) {
            $this->info("ℹ️ Binding for $interface already exists.");
            return;
        }

        $pattern = '/(public function register\(\): void\s*\{)(\n\s*)/';
        $replacement = "$1\n$binding$2";

        if (!preg_match($pattern, $content)) {
            $this->error("❌ Could not find register() method in AppServiceProvider.");
            return;
        }

        $content = preg_replace($pattern, $replacement, $content);
        file_put_contents($providerPath, $content);
        $this->info("✅ Binding for $interface added.");
    }
}
