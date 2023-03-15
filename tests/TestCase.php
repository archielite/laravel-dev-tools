<?php

namespace ArchiElite\LaravelDevTools\Tests;

use ArchiElite\LaravelDevTools\LaravelDevToolsServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDevToolsServiceProvider::class,
        ];
    }
}
