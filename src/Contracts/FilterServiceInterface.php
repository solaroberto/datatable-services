<?php

namespace RobSol66\DataTableServices\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface FilterServiceInterface
{
    public function applyFilters(Builder $query, array $searchBuilder): Builder;
    
    public function filterByDate(Builder $query, string $column, string $keyword): Builder;
    
    public function debugSearchBuilder(array $searchBuilder): void;
}