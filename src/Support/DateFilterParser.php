<?php

namespace RobSol66\DataTableServices\Support;

use DateTime;
use Illuminate\Database\Eloquent\Builder;

class DateFilterParser
{
    /**
     * Filter query by date keyword
     */
    public function filterByDate(Builder $query, string $column, string $keyword): Builder
    {
        $keyword = trim($keyword);

        if (strtolower($keyword) === 'null') {
            return $query->whereNull($column);
        }

        $filterDetails = $this->parseDateFilter($keyword);
        
        if (!$filterDetails) {
            return $query->whereRaw('1=0');
        }

        return $this->applyDateFilter($query, $column, $filterDetails);
    }

    /**
     * Apply parsed date filter to query
     */
    protected function applyDateFilter(Builder $query, string $column, array $filterDetails): Builder
    {
        switch ($filterDetails['type']) {
            case 'year':
                return $query->whereYear($column, $filterDetails['value']);

            case 'year-month':
                $year = $filterDetails['value']['year'];
                $month = str_pad($filterDetails['value']['month'], 2, '0', STR_PAD_LEFT);
                $startOfMonth = "$year-$month-01";
                $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
                
                return $query->whereBetween($column, [$startOfMonth, $endOfMonth]);

            case 'date':
                return $query->whereDate($column, $filterDetails['value']);

            case 'date-range':
                return $query->whereBetween($column, [
                    $filterDetails['value']['start'],
                    $filterDetails['value']['end'],
                ]);

            default:
                return $query->whereRaw('1=0');
        }
    }

    /**
     * Parse date filter keyword
     */
    protected function parseDateFilter(string $keyword): ?array
    {
        $keyword = trim(str_replace('/', '-', $keyword));

        // Date range: "2025-01-01 to 2025-12-31"
        if (preg_match('/^(.+?)\s+to\s+(.+)$/i', $keyword, $matches)) {
            $startDate = $this->parseDate($matches[1]);
            $endDate = $this->parseDate($matches[2]);

            if ($startDate && $endDate) {
                return [
                    'type' => 'date-range',
                    'value' => ['start' => $startDate, 'end' => $endDate],
                ];
            }
        }

        // Year only: "2025"
        if (preg_match('/^\d{4}$/', $keyword)) {
            return ['type' => 'year', 'value' => $keyword];
        }

        // Year and month: "2025-03" or "03-2025"
        if ($this->isYearMonthFormat($keyword)) {
            $parts = $this->parseYearMonth($keyword);
            
            if ($parts && $this->isValidMonth($parts['month'])) {
                return [
                    'type' => 'year-month',
                    'value' => [
                        'year' => $parts['year'],
                        'month' => str_pad($parts['month'], 2, '0', STR_PAD_LEFT),
                    ],
                ];
            }
        }

        // Full date
        $parsedDate = $this->parseDate($keyword);
        if ($parsedDate) {
            return ['type' => 'date', 'value' => $parsedDate];
        }

        return null;
    }

    /**
     * Check if string is in year-month format
     */
    protected function isYearMonthFormat(string $keyword): bool
    {
        return preg_match('/^\d{4}-\d{1,2}$/', $keyword) ||
               preg_match('/^\d{1,2}-\d{4}$/', $keyword);
    }

    /**
     * Parse year and month from string
     */
    protected function parseYearMonth(string $keyword): ?array
    {
        if (preg_match('/^(\d{4})-(\d{1,2})$/', $keyword, $matches)) {
            return ['year' => $matches[1], 'month' => $matches[2]];
        }
        
        if (preg_match('/^(\d{1,2})-(\d{4})$/', $keyword, $matches)) {
            return ['year' => $matches[2], 'month' => $matches[1]];
        }

        return null;
    }

    /**
     * Validate month value
     */
    protected function isValidMonth(string $month): bool
    {
        $monthInt = intval($month);
        return $monthInt >= 1 && $monthInt <= 12;
    }

    /**
     * Parse single date in various formats
     */
    protected function parseDate(string $dateString): ?string
    {
        $dateString = trim($dateString);
        
        $formats = [
            'Y-m-d',     // 2025-03-25
            'd-m-Y',     // 25-03-2025
            'd/m/Y',     // 25/03/2025
            'm/d/Y',     // 03/25/2025
            'Y/m/d',     // 2025/03/25
            'd.m.Y',     // 25.03.2025
            'Y.m.d',     // 2025.03.25
        ];

        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $dateString);
            
            if ($date && $date->format($format) === $dateString) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }
}