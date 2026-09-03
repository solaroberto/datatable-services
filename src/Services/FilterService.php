<?php

namespace RobSol66\DataTableServices\Services;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use RobSol66\DataTableServices\Contracts\FilterServiceInterface;
use RobSol66\DataTableServices\Support\DateFilterParser;

class FilterService implements FilterServiceInterface
{
    protected DateFilterParser $dateParser;

    public function __construct(DateFilterParser $dateParser)
    {
        $this->dateParser = $dateParser;
    }

    /**
     * Apply filters to query based on search criteria
     */
    public function applyFilters(Builder $query, array $searchBuilder): Builder
    {
        if (empty($searchBuilder['criteria'])) {
            return $query;
        }

        $logic = $searchBuilder['logic'] ?? 'AND';

        return $query->where(function ($query) use ($searchBuilder, $logic) {
            $this->processCriteria($query, $searchBuilder['criteria'], $logic);
        });
    }

    /**
     * Process criteria recursively
     */
    protected function processCriteria(Builder $query, array $criteria, string $logic): void
    {
        foreach ($criteria as $index => $criterion) {
            $isFirstCondition = ($index === 0);

            if (isset($criterion['criteria']) && is_array($criterion['criteria'])) {
                $this->processNestedGroup($query, $criterion, $logic, $isFirstCondition);
            } else {
                $this->processSingleCriterion($query, $criterion, $logic, $isFirstCondition);
            }
        }
    }

    /**
     * Process nested criteria group
     */
    protected function processNestedGroup(
        Builder $query, 
        array $group, 
        string $parentLogic, 
        bool $isFirstCondition
    ): void {
        $groupLogic = $group['logic'] ?? 'AND';
        $method = $this->getQueryMethod($parentLogic, $isFirstCondition);

        $query->$method(function ($subQuery) use ($group, $groupLogic) {
            $this->processCriteria($subQuery, $group['criteria'], $groupLogic);
        });
    }

    /**
     * Process single criterion
     */
    protected function processSingleCriterion(
        Builder $query, 
        array $criterion, 
        string $logic, 
        bool $isFirstCondition
    ): void {
        if (!$this->isValidCriterion($criterion)) {
            return;
        }

        $filterCondition = $this->buildFilterCondition($criterion);

        if ($filterCondition) {
            $method = $this->getQueryMethod($logic, $isFirstCondition);

            if (is_array($filterCondition)) {
                $query->$method(...$filterCondition);
            } elseif ($filterCondition instanceof Closure) {
                $query->$method($filterCondition);
            }
        }
    }

    /**
     * Validate criterion
     */
    protected function isValidCriterion(array $criterion): bool
    {
        return isset($criterion['origData'], $criterion['condition'], $criterion['value']) &&
            !empty($criterion['value'][0] ?? null);
    }

    /**
     * Determine query method based on logic and position
     */
    protected function getQueryMethod(string $logic, bool $isFirstCondition): string
    {
        if ($isFirstCondition) {
            return 'where';
        }

        return $logic === 'AND' ? 'where' : 'orWhere';
    }

    /**
     * Build filter condition
     */
    protected function buildFilterCondition(array $criterion): array|Closure|null
    {
        $column = $criterion['origData'];
        $condition = $criterion['condition'];
        $value = $criterion['value'];
        $type = $criterion['type'] ?? 'string';

        // Handle date/datetime types
        if (in_array($type, ['date', 'datetime']) && in_array($condition, ['=', 'contains'])) {
            return $this->buildDateFilterCondition($column, $value[0]);
        }

        return $this->buildStandardCondition($column, $condition, $value);
    }

    /**
     * Build standard filter condition
     */
    protected function buildStandardCondition(string $column, string $condition, array $value): array|Closure|null
    {
        return match($condition) {
            '=', 'equals' => [$column, '=', $value[0]],
            '!=', 'not_equals' => [$column, '!=', $value[0]],
            '<', 'less_than' => [$column, '<', $value[0]],
            '<=', 'less_than_or_equal' => [$column, '<=', $value[0]],
            '>', 'greater_than' => [$column, '>', $value[0]],
            '>=', 'greater_than_or_equal' => [$column, '>=', $value[0]],
            'between' => $this->buildBetweenCondition($column, $value),
            '!between' => $this->buildNotBetweenCondition($column, $value),
            'starts', 'startsWith' => [$column, 'LIKE', $value[0].'%'],
            '!starts', '!startsWith' => [$column, 'NOT LIKE', $value[0].'%'],
            'contains' => [$column, 'LIKE', '%'.$value[0].'%'],
            '!contains' => [$column, 'NOT LIKE', '%'.$value[0].'%'],
            'ends', 'endsWith' => [$column, 'LIKE', '%'.$value[0]],
            '!ends', '!endsWith' => [$column, 'NOT LIKE', '%'.$value[0]],
            'null', 'empty' => function ($query) use ($column) {
                $query->whereNull($column)->orWhere($column, '=', '');
            },
            '!null', '!empty' => function ($query) use ($column) {
                $query->whereNotNull($column)->where($column, '!=', '');
            },
            'in' => $this->buildInCondition($column, $value),
            '!in' => $this->buildNotInCondition($column, $value),
            'regex' => [$column, 'REGEXP', $value[0]],
            '!regex' => [$column, 'NOT REGEXP', $value[0]],
            default => $this->handleUnknownCondition($condition),
        };
    }

    /**
     * Build between condition
     */
    protected function buildBetweenCondition(string $column, array $value): ?Closure
    {
        if (!isset($value[1])) {
            return null;
        }

        return function ($query) use ($column, $value) {
            $query->whereBetween($column, [$value[0], $value[1]]);
        };
    }

    /**
     * Build not between condition
     */
    protected function buildNotBetweenCondition(string $column, array $value): ?Closure
    {
        if (!isset($value[1])) {
            return null;
        }

        return function ($query) use ($column, $value) {
            $query->whereNotBetween($column, [$value[0], $value[1]]);
        };
    }

    /**
     * Build in condition
     */
    protected function buildInCondition(string $column, array $value): ?Closure
    {
        if (!is_array($value) || count($value) === 0) {
            return null;
        }

        return function ($query) use ($column, $value) {
            $query->whereIn($column, $value);
        };
    }

    /**
     * Build not in condition
     */
    protected function buildNotInCondition(string $column, array $value): ?Closure
    {
        if (!is_array($value) || count($value) === 0) {
            return null;
        }

        return function ($query) use ($column, $value) {
            $query->whereNotIn($column, $value);
        };
    }

    /**
     * Handle unknown condition
     */
    protected function handleUnknownCondition(string $condition): null
    {
        Log::warning("Condizione di filtro non riconosciuta: {$condition}");
        return null;
    }

    /**
     * Build date filter condition
     */
    protected function buildDateFilterCondition(string $column, string $keyword): Closure
    {
        return function ($query) use ($column, $keyword) {
            $this->dateParser->filterByDate($query, $column, $keyword);
        };
    }

    /**
     * Filter by date
     */
    public function filterByDate(Builder $query, string $column, string $keyword): Builder
    {
        return $this->dateParser->filterByDate($query, $column, $keyword);
    }

    /**
     * Debug search builder
     */
    public function debugSearchBuilder(array $searchBuilder): void
    {
        Log::debug('SearchBuilder Structure:', [
            'logic' => $searchBuilder['logic'] ?? 'AND',
            'criteria_count' => count($searchBuilder['criteria'] ?? []),
            'criteria' => $searchBuilder['criteria'] ?? [],
        ]);
    }
}