<?php

namespace audunru\ModelHistory\Tests;

use audunru\ModelHistory\ModelHistoryServiceProvider;
use audunru\ModelHistory\Models\Change;
use audunru\ModelHistory\Resources\History;
use CreateModelHistoryTable;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @SuppressWarnings("unused")
     */
    protected function getPackageProviders($app)
    {
        return [ModelHistoryServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true' === env('APP_DEBUG'));
        $app['config']->set('app.key', substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', 5)), 0, 32));
        $app->register(ModelHistoryServiceProvider::class);
    }

    protected function defineRoutes($router)
    {
        $router->get('/history', function () {
            return History::collection(Change::all());
        });
    }

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__.'/../tests/database/migrations');
        $this->artisan('migrate');

        include_once __DIR__.'/../database/migrations/create_model_history_table.php.stub';
        (new CreateModelHistoryTable())->up();
    }
}
