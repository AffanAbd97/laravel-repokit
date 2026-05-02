<?php

namespace Sazl\LaravelRepokit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Sazl\LaravelRepokit\Utils\NameResolver;

class MakeServiceCommand extends Command
{


    protected $signature = 'make:service {name} {--R|repository=} {--empty|e}';
    protected $description = 'Generate a new service with an interface and auto-bind it in AppServiceProvider';

    public function handle()
    {
        $nameResolver = new NameResolver();
        $name = $this->argument('name');
        $repoInput = $this->option('repository');
        $isEmpty = $this->option('e');

        $repo = $nameResolver->repository($repoInput ? $repoInput : $name, true);
        $interfaceName = $nameResolver->service($name, true);
        $serviceName = $nameResolver->service($name);

        $filesystem = new Filesystem();
        $stubPath = __DIR__ . '/../stubs/services';

        $interfaceTemplate = file_get_contents($isEmpty ? "$stubPath/contracts/service-empty.contract.stub" : "$stubPath/contracts/service.contract.stub");
        $interfaceContent = str_replace('{{ interface }}', $interfaceName, $interfaceTemplate);

        $serviceStub = $isEmpty ? 'service-empty.stub' : 'service.stub';
        $serviceTemplate = file_get_contents("$stubPath/$serviceStub");

        $replacements = [
            '{{ service_interface }}' => $interfaceName,
            '{{ service }}' => $serviceName,
            '{{ repository_interface }}' => $repo,
        ];

        foreach ($replacements as $key => $value) {
            $serviceTemplate = str_replace($key, $value, $serviceTemplate);
        }

        $filesystem->ensureDirectoryExists(app_path('Services/Contracts'));
        $filesystem->ensureDirectoryExists(app_path('Services'));

        $filesystem->put(app_path("Services/Contracts/{$interfaceName}.php"), $interfaceContent);
        $filesystem->put(app_path("Services/{$serviceName}.php"), $serviceTemplate);

        $this->addBindingToServiceProvider($interfaceName, $serviceName);
        $this->info("✅ Service and interface created successfully!");
    }

    protected function addBindingToServiceProvider($interface, $service)
    {
        $providerPath = app_path('Providers/AppServiceProvider.php');
        if (!file_exists($providerPath)) {
            $this->error("⚠️ AppServiceProvider.php not found!");
            return;
        }

        $content = file_get_contents($providerPath);
        $binding = "        \$this->app->bind(\\App\\Services\\Contracts\\$interface::class, \\App\\Services\\$service::class);\n";

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
