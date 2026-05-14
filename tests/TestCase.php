<?php

namespace Sazl\LaravelRepokit\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Sazl\LaravelRepokit\Providers\RepokitProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            RepokitProvider::class,
        ];
    }
}
