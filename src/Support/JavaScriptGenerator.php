<?php

namespace RobSol66\DataTableServices\Support;

class JavaScriptGenerator
{
    /**
     * Generate checkbox selection script
     */
    public function generateCheckboxScript(
        string $tableId,
        array $options = []
    ): string {
        $config = array_merge([
            'enableCounter' => true,
            'enableBulkEvents' => true,
            'selectAllSelector' => '#select-all',
            'rowCheckboxSelector' => '.row-checkbox',
        ], $options);

        return $this->renderView('checkbox-script', [
            'tableId' => $tableId,
            'config' => $config,
        ]);
    }

    /**
     * Generate row reorder script
     */
    public function generateRowReorderScript(
        string $tableId,
        array $options = []
    ): string {
        $route = $options['route'] ?? '';
        $successMessage = addslashes($options['successMessage'] ?? 'Ordinamento aggiornato con successo');
        $errorMessage = addslashes($options['errorMessage'] ?? 'Errore durante l\'aggiornamento dell\'ordinamento');
        
        return $this->renderView('row-reorder-script', [
            'tableId' => $tableId,
            'route' => $route,
            'successMessage' => $successMessage,
            'errorMessage' => $errorMessage,
            'csrfToken' => csrf_token(),
        ]);
    }

    /**
     * Generate bulk action script
     */
    public function generateBulkActionScript(
        string $action,
        string $route,
        string $successMessage,
        string $method = 'POST',
        bool $requireCsrf = true,
        ?string $confirmMessage = null,
        bool $useConfirmation = false,
        bool $preserveSelection = false,
        bool $ajax = false
    ): string {
        // Implementation moved here for better separation
        return $this->renderView('bulk-action-script', [
            'action' => $action,
            'route' => $this->resolveRouteUrl($route),
            'successMessage' => $successMessage,
            'method' => $method,
            'requireCsrf' => $requireCsrf,
            'confirmMessage' => $confirmMessage,
            'useConfirmation' => $useConfirmation,
            'preserveSelection' => $preserveSelection,
            'ajax' => $ajax,
        ]);
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
        return $this->renderView('column-click-script', [
            'table' => $table,
            'columnIndex' => $columnIndex,
            'route' => $route,
            'successMessage' => $successMessage,
            'errorMessage' => $errorMessage,
        ]);
    }

    /**
     * Resolve route URL
     */
    protected function resolveRouteUrl(string $route): string
    {
        return (strpos($route, 'http') === 0 || strpos($route, '/') === 0) 
            ? $route 
            : route($route);
    }

    /**
     * Render JavaScript template
     */
    protected function renderView(string $template, array $data): string
    {
        extract($data);
        ob_start();
        include __DIR__ . "/../resources/views/{$template}.php";
        return ob_get_clean();
    }
}