<?php

namespace Sazl\LaravelRepokit\Tests;

class MakeRepositoryCommandTest extends TestCase
{
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
}
