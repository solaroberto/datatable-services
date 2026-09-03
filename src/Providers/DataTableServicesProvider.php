<?php

namespace RobSol66\DataTableServices\Providers;

use Illuminate\Support\ServiceProvider;
use RobSol66\DataTableServices\Contracts\DataTableServiceInterface;
use RobSol66\DataTableServices\Contracts\FilterServiceInterface;
use RobSol66\DataTableServices\Services\DataTableService;
use RobSol66\DataTableServices\Services\FilterService;
use RobSol66\DataTableServices\Support\DateFilterParser;
use RobSol66\DataTableServices\Support\JavaScriptGenerator;

class DataTableServicesProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/datatable-services.php',
            'datatable-services'
        );

        // Register support classes
        $this->app->singleton(JavaScriptGenerator::class);
        $this->app->singleton(DateFilterParser::class);

        // Register services
        $this->app->singleton(DataTableServiceInterface::class, function ($app) {
            return new DataTableService(
                $app->make(JavaScriptGenerator::class),
                config('datatable-services')
            );
        });

        $this->app->singleton(FilterServiceInterface::class, function ($app) {
            return new FilterService(
                $app->make(DateFilterParser::class)
            );
        });

        // Register aliases
        $this->app->alias(DataTableServiceInterface::class, 'datatable-service');
        $this->app->alias(FilterServiceInterface::class, 'filter-service');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/datatable-services.php' => config_path('datatable-services.php'),
        ], 'datatable-services-config');
    }
}