<?php

namespace RobSol66\DataTableServices\Tests\Unit;

use Mockery;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Builder;
use RobSol66\DataTableServices\Services\FilterService;
use RobSol66\DataTableServices\Support\DateFilterParser;

class FilterServiceTest extends TestCase
{
    protected FilterService $service;
    protected DateFilterParser $dateParser;
    protected Builder $query;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->dateParser = Mockery::mock(DateFilterParser::class);
        $this->query = Mockery::mock(Builder::class);
        
        $this->service = new FilterService($this->dateParser);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_returns_query_when_no_criteria_provided()
    {
        $searchBuilder = [
            'criteria' => []
        ];

        $result = $this->service->applyFilters($this->query, $searchBuilder);
        
        $this->assertInstanceOf(Builder::class, $result);
    }

    /** @test */
    public function it_applies_equals_condition()
    {
        $searchBuilder = [
            'logic' => 'AND',
            'criteria' => [
                [
                    'origData' => 'status',
                    'condition' => '=',
                    'value' => ['active'],
                    'type' => 'string',
                ]
            ]
        ];

        $this->query->shouldReceive('where')
            ->once()
            ->with(Mockery::on(function ($callback) {
                // Verify closure is callable
                return is_callable($callback);
            }))
            ->andReturnSelf();

        $result = $this->service->applyFilters($this->query, $searchBuilder);
        
        $this->assertInstanceOf(Builder::class, $result);
    }

    /** @test */
    public function it_applies_contains_condition()
    {
        $searchBuilder = [
            'logic' => 'AND',
            'criteria' => [
                [
                    'origData' => 'name',
                    'condition' => 'contains',
                    'value' => ['John'],
                    'type' => 'string',
                ]
            ]
        ];

        $this->query->shouldReceive('where')
            ->once()
            ->with(Mockery::on(function ($callback) {
                return is_callable($callback);
            }))
            ->andReturnSelf();

        $result = $this->service->applyFilters($this->query, $searchBuilder);
        
        $this->assertInstanceOf(Builder::class, $result);
    }

    /** @test */
    public function it_handles_date_filters()
    {
        $searchBuilder = [
            'criteria' => [
                [
                    'origData' => 'created_at',
                    'condition' => '=',
                    'value' => ['2025-01-15'],
                    'type' => 'date',
                ]
            ]
        ];

        $this->dateParser->shouldReceive('filterByDate')
            ->once()
            ->with(
                Mockery::type(Builder::class),
                'created_at',
                '2025-01-15'
            )
            ->andReturn($this->query);

        $this->query->shouldReceive('where')
            ->once()
            ->with(Mockery::on(function ($callback) {
                return is_callable($callback);
            }))
            ->andReturnSelf();

        $result = $this->service->applyFilters($this->query, $searchBuilder);
        
        $this->assertInstanceOf(Builder::class, $result);
    }

    /** @test */
    public function it_processes_nested_criteria_groups()
    {
        $searchBuilder = [
            'logic' => 'AND',
            'criteria' => [
                [
                    'logic' => 'OR',
                    'criteria' => [
                        [
                            'origData' => 'status',
                            'condition' => '=',
                            'value' => ['active'],
                            'type' => 'string',
                        ],
                        [
                            'origData' => 'status',
                            'condition' => '=',
                            'value' => ['pending'],
                            'type' => 'string',
                        ]
                    ]
                ]
            ]
        ];

        $this->query->shouldReceive('where')
            ->once()
            ->with(Mockery::on(function ($callback) {
                return is_callable($callback);
            }))
            ->andReturnSelf();

        $result = $this->service->applyFilters($this->query, $searchBuilder);
        
        $this->assertInstanceOf(Builder::class, $result);
    }

    /** @test */
    public function it_handles_null_condition()
    {
        $searchBuilder = [
            'criteria' => [
                [
                    'origData' => 'deleted_at',
                    'condition' => 'null',
                    'value' => ['null'],
                    'type' => 'string',
                ]
            ]
        ];

        $this->query->shouldReceive('where')
            ->once()
            ->with(Mockery::on(function ($callback) {
                return is_callable($callback);
            }))
            ->andReturnSelf();

        $result = $this->service->applyFilters($this->query, $searchBuilder);
        
        $this->assertInstanceOf(Builder::class, $result);
    }

    /** @test */
    public function it_validates_criteria()
    {
        $searchBuilder = [
            'criteria' => [
                [
                    'origData' => 'name',
                    'condition' => 'contains',
                    'value' => [], // Empty value
                ]
            ]
        ];

        // Should not call where since criterion is invalid
        $this->query->shouldNotReceive('where');

        $result = $this->service->applyFilters($this->query, $searchBuilder);
        
        $this->assertInstanceOf(Builder::class, $result);
    }
}