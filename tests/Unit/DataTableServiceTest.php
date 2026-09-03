<?php

namespace RobSol66\DataTableServices\Tests\Unit;

use Mockery;
use PHPUnit\Framework\TestCase;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use RobSol66\DataTableServices\Services\DataTableService;
use RobSol66\DataTableServices\Support\JavaScriptGenerator;

class DataTableServiceTest extends TestCase
{
    protected DataTableService $service;
    protected JavaScriptGenerator $jsGenerator;
    protected HtmlBuilder $htmlBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->jsGenerator = Mockery::mock(JavaScriptGenerator::class);
        $this->htmlBuilder = Mockery::mock(HtmlBuilder::class);
        
        $this->service = new DataTableService(
            $this->jsGenerator,
            []
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_configures_html_builder_with_default_parameters()
    {
        $tableId = 'test-table';
        
        $this->htmlBuilder->shouldReceive('setTableId')
            ->once()
            ->with($tableId)
            ->andReturnSelf();
            
        $this->htmlBuilder->shouldReceive('minifiedAjax')
            ->once()
            ->andReturnSelf();
            
        $this->htmlBuilder->shouldReceive('parameters')
            ->once()
            ->with(Mockery::on(function ($params) {
                return isset($params['processing']) && 
                       $params['processing'] === true &&
                       isset($params['serverSide']) && 
                       $params['serverSide'] === true;
            }))
            ->andReturnSelf();
            
        $this->htmlBuilder->shouldReceive('selectStyleMultiShift')
            ->once()
            ->andReturnSelf();
            
        $this->htmlBuilder->shouldReceive('layout')
            ->once()
            ->andReturnSelf();
            
        $this->htmlBuilder->shouldReceive('buttons')
            ->once()
            ->andReturnSelf();

        $result = $this->service->configureHtml(
            $this->htmlBuilder,
            $tableId
        );

        $this->assertInstanceOf(HtmlBuilder::class, $result);
    }

    /** @test */
    public function it_enables_checkboxes_when_requested()
    {
        $tableId = 'test-table';
        $checkboxScript = 'function() { /* checkbox script */ }';
        
        $this->jsGenerator->shouldReceive('generateCheckboxScript')
            ->once()
            ->with($tableId, [])
            ->andReturn($checkboxScript);
            
        $this->htmlBuilder->shouldReceive('setTableId')->andReturnSelf();
        $this->htmlBuilder->shouldReceive('minifiedAjax')->andReturnSelf();
        $this->htmlBuilder->shouldReceive('parameters')
            ->once()
            ->with(Mockery::on(function ($params) {
                return isset($params['select']) && 
                       $params['select']['style'] === 'multi' &&
                       strpos($params['initComplete'] ?? '', 'checkbox script') !== false;
            }))
            ->andReturnSelf();
        $this->htmlBuilder->shouldReceive('selectStyleMultiShift')->andReturnSelf();
        $this->htmlBuilder->shouldReceive('layout')->andReturnSelf();
        $this->htmlBuilder->shouldReceive('buttons')->andReturnSelf();

        $result = $this->service->configureHtml(
            $this->htmlBuilder,
            $tableId,
            [],
            [],
            true // enableCheckboxes
        );

        $this->assertInstanceOf(HtmlBuilder::class, $result);
    }

    /** @test */
    public function it_enables_row_reorder_when_requested()
    {
        $tableId = 'test-table';
        $reorderScript = 'function() { /* reorder script */ }';
        $rowReorderOptions = [
            'route' => 'reorder.route',
            'dataSrc' => 'order',
        ];
        
        $this->jsGenerator->shouldReceive('generateRowReorderScript')
            ->once()
            ->with($tableId, $rowReorderOptions)
            ->andReturn($reorderScript);
            
        $this->htmlBuilder->shouldReceive('setTableId')->andReturnSelf();
        $this->htmlBuilder->shouldReceive('minifiedAjax')->andReturnSelf();
        $this->htmlBuilder->shouldReceive('parameters')
            ->once()
            ->with(Mockery::on(function ($params) use ($reorderScript) {
                return isset($params['rowReorder']) && 
                       isset($params['initComplete']) &&
                       strpos($params['initComplete'], $reorderScript) !== false;
            }))
            ->andReturnSelf();
        $this->htmlBuilder->shouldReceive('selectStyleMultiShift')->andReturnSelf();
        $this->htmlBuilder->shouldReceive('layout')->andReturnSelf();
        $this->htmlBuilder->shouldReceive('buttons')->andReturnSelf();

        $result = $this->service->configureHtml(
            $this->htmlBuilder,
            $tableId,
            [],
            [],
            false, // enableCheckboxes
            false, // enableSearchBuilder
            [],    // checkboxOptions
            true,  // enableRowReorder
            $rowReorderOptions
        );

        $this->assertInstanceOf(HtmlBuilder::class, $result);
    }

    /** @test */
    public function it_gets_standard_buttons()
    {
        $buttons = $this->service->getButtons();
        
        $this->assertIsArray($buttons);
        $this->assertCount(2, $buttons);
    }

    /** @test */
    public function it_adds_bulk_action_buttons()
    {
        $actions = [
            [
                'text' => 'Delete Selected',
                'action' => 'delete',
                'route' => 'bulk.delete',
                'successMessage' => 'Items deleted successfully',
                'method' => 'POST',
                'confirmation' => true,
            ]
        ];
        
        $this->jsGenerator->shouldReceive('generateBulkActionScript')
            ->once()
            ->andReturn('function(e, dt, button, config) { /* bulk action */ }');

        $buttons = $this->service->addBulkActionButtons($actions);
        
        $this->assertIsArray($buttons);
        $this->assertCount(1, $buttons);
        $this->assertInstanceOf(\Yajra\DataTables\Html\Button::class, $buttons[0]);
    }
}