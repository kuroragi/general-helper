<?php

namespace Kuroragi\GeneralHelper\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Kuroragi\GeneralHelper\Providers\GeneralHelperServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            GeneralHelperServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}
