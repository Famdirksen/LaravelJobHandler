<?php

namespace Famdirksen\LaravelJobHandler\Tests;

use Famdirksen\LaravelJobHandler\LaravelJobHandlerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../src/migrations');
    }
    protected function getPackageProviders($app): array
    {
        return [
            LaravelJobHandlerServiceProvider::class,
        ];
    }
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');

        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
