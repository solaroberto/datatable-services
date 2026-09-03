<?php

namespace RobSol66\DataTableServices\Services;

use Illuminate\Support\Facades\Session;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use RobSol66\DataTableServices\Contracts\DataTableServiceInterface;
use RobSol66\DataTableServices\Support\JavaScriptGenerator;

class DataTableService implements DataTableServiceInterface
{
    protected JavaScriptGenerator $jsGenerator;
    protected array $config;

    public function __construct(JavaScriptGenerator $jsGenerator, array $config = [])
    {
        $this->jsGenerator = $jsGenerator;
        $this->config = $config;
    }

    /**
     * Configure HTML builder with common DataTable parameters
     */
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
    ): HtmlBuilder {
        $parameters = $this->buildCommonParameters($tableId, $enableCheckboxes, $checkboxOptions, $enableRowReorder, $rowReorderOptions, $customParameters);
        $buttons = !empty($customButtons) ? $customButtons : $this->getButtons();

        return $builder
            ->setTableId($tableId)
            ->minifiedAjax()
            ->parameters($parameters)
            ->selectStyleMultiShift()
            ->layout($this->getLayout($enableSearchBuilder))
            ->buttons($buttons);
    }

    /**
     * Build common DataTable parameters
     */
    protected function buildCommonParameters(
        string $tableId,
        bool $enableCheckboxes,
        array $checkboxOptions,
        bool $enableRowReorder,
        array $rowReorderOptions,
        array &$customParameters
    ): array {
        $parameters = $this->getDefaultParameters();

        if ($enableCheckboxes) {
            $this->configureCheckboxes($parameters, $customParameters, $tableId, $checkboxOptions);
        }

        if ($enableRowReorder) {
            $this->configureRowReorder($parameters, $customParameters, $tableId, $rowReorderOptions);
        }

        if (!empty($customParameters['initComplete'])) {
            $this->normalizeInitComplete($customParameters);
        }

        return array_merge($parameters, $customParameters);
    }

    /**
     * Get default DataTable parameters
     */
    protected function getDefaultParameters(): array
    {
        $locale = Session::get('locale', 'it');
        
        return [
            'processing' => true,
            'serverSide' => true,
            'paging' => true,
            'searching' => true,
            'searchDelay' => 800,
            'search' => ['smart' => false, 'regex' => false],
            'info' => true,
            'responsive' => false,
            'stateSave' => false,
            'pageLength' => $this->config['page_length'] ?? 25,
            'lengthChange' => true,
            'language' => $this->getLanguageConfig($locale),
            'drawCallback' => $this->getDrawCallback(),
        ];
    }

    /**
     * Get language configuration
     */
    protected function getLanguageConfig(string $locale): array
    {
        $langUrls = $this->config['language_urls'] ?? [
            'it' => 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/it-IT.json',
            'en' => 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/en-GB.json',
            'fr' => 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json',
            'de' => 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/de-DE.json',
            'ar' => 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json',
        ];

        return [
            'oPaginate' => [
                'sFirst' => '<<',
                'sPrevious' => '<',
                'sNext' => '>',
                'sLast' => '>>',
            ],
            'url' => $langUrls[$locale] ?? $langUrls['it'],
        ];
    }

    /**
     * Configure checkbox support
     */
    protected function configureCheckboxes(
        array &$parameters,
        array &$customParameters,
        string $tableId,
        array $checkboxOptions
    ): void {
        $parameters['select'] = [
            'style' => 'multi',
            'selector' => '.row-checkbox',
            'stateSave' => false,
        ];

        $checkboxScript = $this->jsGenerator->generateCheckboxScript($tableId, $checkboxOptions);
        $existingInitComplete = $customParameters['initComplete'] ?? '';
        
        $customParameters['initComplete'] = $existingInitComplete
            ? $this->combineInitCompleteScripts($existingInitComplete, $checkboxScript)
            : "function(settings, json) { {$checkboxScript} }";
    }

    /**
     * Configure row reorder support
     */
    protected function configureRowReorder(
        array &$parameters,
        array &$customParameters,
        string $tableId,
        array $rowReorderOptions
    ): void {
        $parameters['rowReorder'] = [
            'dataSrc' => $rowReorderOptions['dataSrc'] ?? 'order',
            'selector' => $rowReorderOptions['selector'] ?? 'td:not(:first-child):not(:last-child)',
            'update' => $rowReorderOptions['update'] ?? false,
        ];

        if (!empty($rowReorderOptions['route'])) {
            $reorderScript = $this->jsGenerator->generateRowReorderScript($tableId, $rowReorderOptions);
            $existingInitComplete = $customParameters['initComplete'] ?? '';
            
            $customParameters['initComplete'] = $existingInitComplete
                ? $this->combineInitCompleteScripts($existingInitComplete, $reorderScript)
                : "function(settings, json) { {$reorderScript} }";
        }
    }

    /**
     * Normalize initComplete callback
     */
    protected function normalizeInitComplete(array &$customParameters): void
    {
        $existing = trim($customParameters['initComplete']);
        if (!preg_match('/^function\s*\(/', $existing)) {
            $customParameters['initComplete'] = "function(settings, json) {\n{$existing}\n}";
        }
    }

    /**
     * Combine multiple initComplete scripts
     */
    protected function combineInitCompleteScripts(string $existingScript, string $newScript): string
    {
        $cleanExistingScript = preg_replace(
            '/^function\s*\([^)]*\)\s*\{(.*)\}$/s', 
            '$1', 
            trim($existingScript)
        );

        return "function(settings, json) {
            {$cleanExistingScript}
            {$newScript}
        }";
    }

    /**
     * Get standard buttons
     */
    public function getButtons(array $additionalButtons = []): array
    {
        $standardButtons = [
            $this->getColumnVisibilityButton(),
            $this->getExportButtonsGroup(),
        ];

        return array_merge($additionalButtons, $standardButtons);
    }

    /**
     * Get column visibility button
     */
    public function getColumnVisibilityButton(): Button
    {
        return Button::make('create')
            ->extend('colvis')
            ->attr(['class' => 'btn btn-outline-secondary waves-effect'])
            ->exportOptions(['columns' => ':visible']);
    }

    /**
     * Get export buttons group
     */
    protected function getExportButtonsGroup(): Button
    {
        return Button::make()
            ->text('<span class="d-flex align-items-center"><i class="icon-base ri ri-external-link-line icon-18px"></i> <span class="d-none d-sm-inline-block">'.__('global.datatables.export').'</span></span>')
            ->extend('collection')
            ->attr(['class' => 'btn btn-outline-secondary waves-effect ms-2'])
            ->buttons([
                $this->getExcelButton(),
                $this->getCsvButton(),
                $this->getPdfButton(),
                $this->getPrintButton(),
                $this->getCopyButton(),
            ]);
    }

    /**
     * Add bulk action buttons
     */
    public function addBulkActionButtons(array $actions = []): array
    {
        $bulkButtons = [];

        foreach ($actions as $action) {
            $bulkButtons[] = Button::make()
                ->text($action['text'])
                ->addClass('dropdown-item')
                ->action($this->jsGenerator->generateBulkActionScript(
                    $action['action'],
                    $action['route'],
                    $action['successMessage'],
                    $action['method'] ?? 'POST',
                    $action['csrf'] ?? true,
                    $action['confirmMessage'] ?? null,
                    $action['confirmation'] ?? false,
                    $action['preserveSelection'] ?? false,
                    $action['ajax'] ?? false
                ));
        }

        return $bulkButtons;
    }

    /**
     * Generate initComplete script
     */
    public function initComplete(
        string $table, 
        array $inputColumns, 
        array $selectColumns, 
        array $notVisibleColumns = []
    ): string {
        return $this->jsGenerator->generateInitCompleteScript(
            $table, 
            $inputColumns, 
            $selectColumns, 
            $notVisibleColumns
        );
    }

    /**
     * Generate column click script
     */
    public function generateColumnClickScript(
        string $table, 
        int $columnIndex, 
        string $route, 
        string $successMessage, 
        string $errorMessage
    ): string {
        return $this->jsGenerator->generateColumnClickScript(
            $table, 
            $columnIndex, 
            $route, 
            $successMessage, 
            $errorMessage
        );
    }

    /**
     * Get layout configuration
     */
    protected function getLayout(bool $enableSearchBuilder): array
    {
        return [
            'top1Start' => [
                'buttons' => $enableSearchBuilder ? [
                    [
                        'extend' => 'searchBuilder',
                        'text' => __('global.AdvancedSearch'),
                        'className' => 'btn bg-gradient-primary btn-sm mb-0 dropdown-toggle me-2',
                        'config' => ['depthLimit' => 2],
                    ],
                ] : [],
            ],
            'top1End' => 'buttons',
        ];
    }

    /**
     * Get draw callback
     */
    protected function getDrawCallback(): string
    {
        return 'function() {
            $(".select2-dt").select2({ width: "resolve" });
            $(\'body\').tooltip({ selector: \'[data-bs-toggle="tooltip"]\' });
            $(this.api().table().container()).addClass("mb-5");

            var tableId = this.api().table().node().id;
            $("#" + tableId + "_info").addClass("text-xs");

            $(".show_confirm").off("click").on("click", function(e) {
                var form = $(this).closest("form");
                e.preventDefault();
                Swal.fire({
                    title: "Elimina",
                    text: "Sei sicuro di voler eliminare il record selezionato?",
                    showCancelButton: true,
                    cancelButtonText: "Annulla",
                    confirmButtonText: "Sì, procedi",
                    icon: "warning",
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            $(".show_confirm_cancel").off("click").on("click", function(e) {
                var form = $(this).closest("form");
                e.preventDefault();
                Swal.fire({
                    title: "Annulla Invio",
                    text: "Sei sicuro di voler annullare l\'invio? I messaggi programmati non verranno inviati.",
                    showCancelButton: true,
                    cancelButtonText: "No, continua",
                    confirmButtonText: "Sì, annulla",
                    icon: "warning",
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        }';
    }
}