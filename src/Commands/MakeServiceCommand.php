<?php

namespace Sazl\LaravelRepokit\Commands;

use Illuminate\Filesystem\Filesystem;
use Sazl\LaravelRepokit\CommandGenerator;
use Sazl\LaravelRepokit\Utils\ConfigWriter;
use Sazl\LaravelRepokit\Utils\ConfigWriteResult;
use Sazl\LaravelRepokit\Utils\NameResolver;
use Sazl\LaravelRepokit\Utils\StubResolver;

class MakeServiceCommand extends CommandGenerator
{
    protected $signature = 'make:service {name} {--R|repository=} {--e|empty}';
    protected $description = 'Generate a new service with an interface and register the binding in config';

    protected ConfigWriter $configWriter;

    public function __construct(Filesystem $filesystem, NameResolver $resolver, StubResolver $stubResolver, ConfigWriter $configWriter)
    {
        parent::__construct($filesystem, $resolver, $stubResolver);
        $this->configWriter = $configWriter;
    }

    public function handle()
    {
        $name = $this->argument('name');
        $repoInput = $this->option('repository');
        $isEmpty = $this->option('empty');

        $interfaceName = $this->resolver->service($name, true);
        $serviceName = $this->resolver->service($name);
        $repositoryInterface = $this->resolver->repository($repoInput ?: $name, true);

        // Render contract stub
        $contractVariant = $isEmpty ? 'contract.empty' : 'contract';
        $interfaceContent = $this->stubResolver->render('services', $contractVariant, [
            '{{ interface }}' => $interfaceName,
        ]);

        // Render implementation stub
        $implVariant = $isEmpty ? 'implementation.empty' : 'implementation';
        $serviceContent = $this->stubResolver->render('services', $implVariant, [
            '{{ service_interface }}' => $interfaceName,
            '{{ service }}' => $serviceName,
            '{{ repository_interface }}' => $repositoryInterface,
        ]);

        // Write files
        $contractPath = $this->getTargetPath("Contracts/{$interfaceName}.php");
        $servicePath = $this->getTargetPath("{$serviceName}.php");

        $this->write($contractPath, $interfaceContent);
        $this->write($servicePath, $serviceContent);

        // Register binding in config
        $interfaceFqcn = "App\\Services\\Contracts\\{$interfaceName}";
        $implementationFqcn = "App\\Services\\{$serviceName}";

        $configPath = config_path('service.php');

        // Auto-publish config if it doesn't exist yet
        if (!file_exists($configPath)) {
            $this->call('vendor:publish', ['--tag' => 'service-config']);
        }

        $result = $this->configWriter->addBinding($configPath, $interfaceFqcn, $implementationFqcn);

        match ($result) {
            ConfigWriteResult::SUCCESS => $this->info("Binding for {$interfaceName} registered in config/service.php."),
            ConfigWriteResult::ALREADY_EXISTS => $this->info("Binding for {$interfaceName} already exists in config/service.php."),
            ConfigWriteResult::FILE_NOT_FOUND => $this->error("Config file not found. Please publish it using: php artisan vendor:publish --tag=service-config"),
            ConfigWriteResult::NOT_WRITABLE => $this->error("Config file config/service.php is not writable."),
        };

        // Output absolute file paths of generated files
        $this->info($contractPath);
        $this->info($servicePath);
    }

    protected function getTargetPath(string $name): string
    {
        return app_path("Services/{$name}");
    }
}
