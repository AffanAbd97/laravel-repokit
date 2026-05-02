<?php

namespace Sazl\LaravelRepokit\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Sazl\LaravelRepokit\RepositoryProvider;
use Sazl\LaravelRepokit\ServiceLayerProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            RepositoryProvider::class,
            ServiceLayerProvider::class,
        ];
    }
}
