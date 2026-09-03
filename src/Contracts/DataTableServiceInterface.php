<?php

namespace RobSol66\DataTableServices\Contracts;

use Yajra\DataTables\Html\Builder as HtmlBuilder;

interface DataTableServiceInterface
{
    public function configureHtml(
        HtmlBuilder $builder,
        string $tableId,
        array $customParameters = [],
        array $customButtons = [],
        bool $enableCheckboxes = false,
        bool $enableSearchBuilder = false,
        array $checkboxOptions = [],
        bool $enableRowReorder = false,
        array $rowReorderOptions = []
    ): HtmlBuilder;

    public function getButtons(array $additionalButtons = []): array;
    
    public function addBulkActionButtons(array $actions = []): array;
    
    public function initComplete(
        string $table, 
        array $inputColumns, 
        array $selectColumns, 
        array $notVisibleColumns = []
    ): string;
}