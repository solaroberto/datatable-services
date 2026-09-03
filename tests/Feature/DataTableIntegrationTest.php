<?php

namespace RobSol66\DataTableServices\Tests\Feature;

use Orchestra\Testbench\TestCase;
use RobSol66\DataTableServices\Providers\DataTableServicesProvider;
use RobSol66\DataTableServices\Facades\DataTableService;
use RobSol66\DataTableServices\Facades\FilterService;

class DataTableIntegrationTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            DataTableServicesProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'DataTableService' => DataTableService::class,
            'FilterService' => FilterService::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /** @test */
    public function it_registers_services_correctly()
    {
        $this->assertTrue($this->app->bound('datatable-service'));
        $this->assertTrue($this->app->bound('filter-service'));
    }

    /** @test */
    public function it_can_resolve_services_from_container()
    {
        $dataTableService = $this->app->make('datatable-service');
        $filterService = $this->app->make('filter-service');

        $this->assertInstanceOf(
            \RobSol66\DataTableServices\Services\DataTableService::class,
            $dataTableService
        );
        
        $this->assertInstanceOf(
            \RobSol66\DataTableServices\Services\FilterService::class,
            $filterService
        );
    }

    /** @test */
    public function it_can_access_services_via_facades()
    {
        $buttons = DataTableService::getButtons();
        
        $this->assertIsArray($buttons);
        $this->assertNotEmpty($buttons);
    }

    /** @test */
    public function it_loads_configuration()
    {
        $config = config('datatable-services');
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('page_length', $config);
        $this->assertArrayHasKey('language_urls', $config);
    }
}