<?php

namespace audunru\ModelHistory;

use audunru\ModelHistory\Events\HistoryChanged;
use audunru\ModelHistory\Listeners\RecordHistoryChange;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ModelHistoryServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('model-history')
            ->hasConfigFile()
            ->hasMigration('create_model_history_table');
    }

    public function packageBooted()
    {
        Event::listen(
            HistoryChanged::class,
            RecordHistoryChange::class
        );
    }
}
