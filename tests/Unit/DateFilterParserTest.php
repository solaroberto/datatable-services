<?php

namespace RobSol66\DataTableServices\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RobSol66\DataTableServices\Support\DateFilterParser;

class DateFilterParserTest extends TestCase
{
    protected DateFilterParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new DateFilterParser();
    }

    /** @test */
    public function it_parses_year_only_format()
    {
        $result = $this->parser->parseDateFilter('2025');
        
        $this->assertIsArray($result);
        $this->assertEquals('year', $result['type']);
        $this->assertEquals('2025', $result['value']);
    }

    /** @test */
    public function it_parses_year_month_format()
    {
        $result = $this->parser->parseDateFilter('2025-03');
        
        $this->assertIsArray($result);
        $this->assertEquals('year-month', $result['type']);
        $this->assertEquals('2025', $result['value']['year']);
        $this->assertEquals('03', $result['value']['month']);
    }

    /** @test */
    public function it_parses_year_month_format_reversed()
    {
        $result = $this->parser->parseDateFilter('03-2025');
        
        $this->assertIsArray($result);
        $this->assertEquals('year-month', $result['type']);
        $this->assertEquals('2025', $result['value']['year']);
        $this->assertEquals('03', $result['value']['month']);
    }

    /** @test */
    public function it_parses_full_date_format()
    {
        $result = $this->parser->parseDateFilter('2025-03-25');
        
        $this->assertIsArray($result);
        $this->assertEquals('date', $result['type']);
        $this->assertEquals('2025-03-25', $result['value']);
    }

    /** @test */
    public function it_parses_date_range_format()
    {
        $result = $this->parser->parseDateFilter('2025-01-01 to 2025-12-31');
        
        $this->assertIsArray($result);
        $this->assertEquals('date-range', $result['type']);
        $this->assertEquals('2025-01-01', $result['value']['start']);
        $this->assertEquals('2025-12-31', $result['value']['end']);
    }

    /** @test */
    public function it_handles_invalid_date_format()
    {
        $result = $this->parser->parseDateFilter('invalid-date');
        
        $this->assertNull($result);
    }

    /** @test */
    public function it_validates_month_values()
    {
        $validResult = $this->parser->parseDateFilter('2025-12');
        $this->assertIsArray($validResult);
        
        $invalidResult = $this->parser->parseDateFilter('2025-13');
        $this->assertNull($invalidResult);
    }
}