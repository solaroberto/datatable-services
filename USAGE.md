# DataTable Services - Guida all'Utilizzo

## Indice

- [Installazione](#installazione)
- [Configurazione](#configurazione)
- [Setup Base](#setup-base)
- [Configurazione DataTable](#configurazione-datatable)
- [Sistema di Filtri](#sistema-di-filtri)
- [Selezione Checkbox](#selezione-checkbox)
- [Azioni di Massa](#azioni-di-massa)
- [SearchBuilder](#searchbuilder)
- [Riordino Righe](#riordino-righe)
- [Script per Colonne](#script-per-colonne)
- [Opzioni di Export](#opzioni-di-export)
- [Funzionalità Avanzate](#funzionalità-avanzate)
- [Best Practices](#best-practices)
- [Risoluzione Problemi](#risoluzione-problemi)
- [Esempi Completi](#esempi-completi)

---

## Installazione

```bash
composer require solaroberto/datatable-services
```


## Configurazione

Pubblica il file di configurazione:

bash

```
php artisan vendor:publish --tag="datatable-services-config"
```


### Struttura Configurazione

php

```
// config/datatable-services.php
return [
    'page_length' => 25,
    
    'language_urls' => [
        'it' => 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/it-IT.json',
        'en' => 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/en-GB.json',
        'fr' => 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json',
        'de' => 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/de-DE.json',
        'ar' => 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json',
    ],
    
    'checkbox' => [
        'enable_counter' => true,
        'enable_bulk_events' => true,
        'select_all_selector' => '#select-all',
        'row_checkbox_selector' => '.row-checkbox',
    ],
    
    'exports' => [
        'csv' => true,
        'excel' => true,
        'pdf' => true,
        'print' => true,
        'copy' => true,
    ],
];
```


---

## Setup Base

### 1. Creare una Classe DataTable

php

```
<?php

namespace App\DataTables;

use Yajra\DataTables\Html\Builder as HtmlBuilder;
use RobSol66\DataTableServices\Services\DataTableService;
use RobSol66\DataTableServices\Services\FilterService;

class AssetDataTable extends DataTable
{
    protected DataTableService $dataTableService;
    protected FilterService $filterService;

    public function __construct(
        DataTableService $dataTableService,
        FilterService $filterService
    ) {
        parent::__construct();
        $this->dataTableService = $dataTableService;
        $this->filterService = $filterService;
    }

    /**
     * Configura il builder HTML
     */
    public function html(): HtmlBuilder
    {
        return $this->dataTableService->configureHtml(
            $this->builder(),
            'assets-table'  // ID della tabella
        );
    }

    /**
     * Ottieni la sorgente della query
     */
    public function query()
    {
        $query = Asset::query();
        
        // Applica i filtri del search builder
        return $this->filterService->applyFilters(
            $query,
            request('searchBuilder', [])
        );
    }

    /**
     * Definisci le colonne
     */
    protected function getColumns(): array
    {
        return [
            'id',
            'name',
            'status',
            'created_at',
            // ... altre colonne
        ];
    }
}
```


### 2. Utilizzo nel Controller

php

```
<?php

namespace App\Http\Controllers;

use App\DataTables\AssetDataTable;
use App\Http\Controllers\Controller;

class AssetController extends Controller
{
    public function index(AssetDataTable $dataTable)
    {
        return $dataTable->render('assets.index');
    }
}
```


### 3. Creare la View

blade

```
{{-- resources/views/assets/index.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                {!! $dataTable->table(['class' => 'table table-striped table-bordered']) !!}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
@endpush
```


---

## Configurazione DataTable

### Configurazione Base

php

```
public function html(): HtmlBuilder
{
    return $this->dataTableService->configureHtml(
        $this->builder(),
        'assets-table'
    );
}
```


### Parametri Personalizzati

php

```
public function html(): HtmlBuilder
{
    $customParameters = [
        'pageLength' => 50,
        'order' => [[0, 'desc']],
        'dom' => 'Bfrtip',
        'buttons' => ['excel', 'pdf'],
    ];

    return $this->dataTableService->configureHtml(
        $this->builder(),
        'assets-table',
        $customParameters
    );
}
```


### Bottoni Personalizzati

php

```
public function html(): HtmlBuilder
{
    $customButtons = [
        Button::make('create')
            ->text('Aggiungi Asset')
            ->action('function() { window.location.href = "/assets/create"; }')
            ->addClass('btn btn-primary'),
    ];

    return $this->dataTableService->configureHtml(
        $this->builder(),
        'assets-table',
        [],
        $customButtons
    );
}
```


### Firma Completa del Metodo

php

```
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
): HtmlBuilder
```


---

## Sistema di Filtri

### Integrazione SearchBuilder

Il FilterService supporta criteri di ricerca complessi con gruppi annidati:

php

```
public function query()
{
    $query = Asset::query();
    
    return $this->filterService->applyFilters(
        $query,
        request('searchBuilder', [])
    );
}
```


### Struttura Richiesta SearchBuilder

json

```
{
    "logic": "AND",
    "criteria": [
        {
            "origData": "status",
            "condition": "=",
            "value": ["active"],
            "type": "string"
        },
        {
            "logic": "OR",
            "criteria": [
                {
                    "origData": "name",
                    "condition": "contains",
                    "value": ["John"],
                    "type": "string"
                },
                {
                    "origData": "email",
                    "condition": "contains",
                    "value": ["john"],
                    "type": "string"
                }
            ]
        }
    ]
}
```


### Condizioni Supportate

| CondizioneDescrizioneEsempio |                      |                                  |
| ---------------------------- | -------------------- | -------------------------------- |
| `=`                          | Uguale               | `status = active`                |
| `!=`                         | Non uguale           | `status != inactive`             |
| `<`                          | Minore di            | `age < 30`                       |
| `<=`                         | Minore o uguale      | `age <= 30`                      |
| `>`                          | Maggiore di          | `age > 18`                       |
| `>=`                         | Maggiore o uguale    | `age >= 18`                      |
| `between`                    | Tra due valori       | `age between 18 and 30`          |
| `!between`                   | Non tra              | `age !between 18 and 30`         |
| `starts`                     | Inizia con           | `name starts with 'J'`           |
| `!starts`                    | Non inizia con       | `name !starts with 'J'`          |
| `contains`                   | Contiene             | `name contains 'ohn'`            |
| `!contains`                  | Non contiene         | `name !contains 'xyz'`           |
| `ends`                       | Finisce con          | `name ends with 'n'`             |
| `!ends`                      | Non finisce con      | `name !ends with 'x'`            |
| `null`                       | È null o vuoto       | `deleted_at is null`             |
| `!null`                      | Non è null           | `deleted_at is not null`         |
| `in`                         | In lista             | `status in [active, pending]`    |
| `!in`                        | Non in lista         | `status !in [inactive, deleted]` |
| `regex`                      | Espressione regolare | `email regex '.*@gmail\.com$'`   |

### Filtri Data

Il FilterService supporta filtri data intelligenti con formati multipli:

php

```
// Nel metodo query
$searchBuilder = request('searchBuilder', []);

// Esempi di filtri data:
// "2025" - Filtra per anno
// "2025-03" - Filtra per anno e mese
// "2025-03-25" - Filtra per data esatta
// "2025-01-01 to 2025-12-31" - Filtra per intervallo date
```


### Formati Data Supportati

| FormatoEsempioDescrizione |                            |                        |
| ------------------------- | -------------------------- | ---------------------- |
| Anno                      | `2025`                     | Filtra solo per anno   |
| Anno-Mese                 | `2025-03`                  | Filtra per anno e mese |
| Data Completa             | `2025-03-25`               | Filtra per data esatta |
| Intervallo                | `2025-01-01 to 2025-12-31` | Filtra tra date        |
| Formati Alternativi       | `25/03/2025`, `03/25/2025` | Vari formati data      |

---

## Selezione Checkbox

### Abilitare Checkbox

php

```
public function html(): HtmlBuilder
{
    return $this->dataTableService->configureHtml(
        $this->builder(),
        'assets-table',
        [],    // customParameters
        [],    // customButtons
        true   // enableCheckboxes
    );
}
```


### Opzioni Checkbox Personalizzate

php

```
public function html(): HtmlBuilder
{
    $checkboxOptions = [
        'enableCounter' => true,
        'enableBulkEvents' => true,
        'selectAllSelector' => '#select-all-assets',
        'rowCheckboxSelector' => '.asset-checkbox',
    ];

    return $this->dataTableService->configureHtml(
        $this->builder(),
        'assets-table',
        [],               // customParameters
        [],               // customButtons
        true,             // enableCheckboxes
        false,            // enableSearchBuilder
        $checkboxOptions  // checkboxOptions
    );
}
```


### Aggiungere Colonna Checkbox

php

```
protected function getColumns(): array
{
    return [
        Column::make('checkbox')
            ->title('<input type="checkbox" id="select-all" class="form-check-input">')
            ->orderable(false)
            ->searchable(false)
            ->exportable(false)
            ->printable(false)
            ->width('40px')
            ->addClass('text-center')
            ->render(function ($row) {
                return '<input type="checkbox" class="row-checkbox form-check-input" value="' . $row->id . '">';
            }),
        'id',
        'name',
        // ... altre colonne
    ];
}
```


### API JavaScript

Il sistema checkbox fornisce un'API JavaScript per gestire le selezioni:

javascript

```
// Ottieni gli ID delle righe selezionate
var selectedIds = window.getSelectedRowIds('assets-table');

// Ottieni i dati delle righe selezionate
var selectedData = window.getSelectedRowsData('assets-table');

// Cancella tutte le selezioni
window.forceCleanSelections('assets-table');

// Ricarica la tabella con/senza preservare le selezioni
window.reloadTable('assets-table', true); // preserva selezioni
window.reloadTable('assets-table', false); // cancella selezioni
```


---

## Azioni di Massa

### Aggiungere Bottoni Azioni di Massa

php

```
public function html(): HtmlBuilder
{
    $bulkActions = [
        [
            'text' => 'Elimina Selezionati',
            'action' => 'delete',
            'route' => 'assets.bulk-delete',
            'successMessage' => 'Asset eliminati con successo',
            'method' => 'POST',
            'confirmation' => true,
            'confirmMessage' => 'Sei sicuro di voler eliminare gli asset selezionati?',
        ],
        [
            'text' => 'Aggiorna Stato',
            'action' => 'update-status',
            'route' => 'assets.bulk-status',
            'successMessage' => 'Stato aggiornato con successo',
            'method' => 'POST',
            'ajax' => true,
            'preserveSelection' => true,
        ],
        [
            'text' => 'Esporta Selezionati',
            'action' => 'export',
            'route' => 'assets.bulk-export',
            'successMessage' => 'Export avviato',
            'method' => 'POST',
            'csrf' => true,
        ],
    ];

    $buttons = $this->dataTableService->addBulkActionButtons($bulkActions);

    return $this->dataTableService->configureHtml(
        $this->builder(),
        'assets-table',
        [],       // customParameters
        $buttons, // customButtons con azioni di massa
        true      // enableCheckboxes
    );
}
```


### Opzioni Azioni di Massa

| OpzioneTipoDefaultDescrizione |        |           |                                      |
| ----------------------------- | ------ | --------- | ------------------------------------ |
| `text`                        | string | richiesto | Testo del bottone                    |
| `action`                      | string | richiesto | Identificatore azione                |
| `route`                       | string | richiesto | URL della rotta                      |
| `successMessage`              | string | richiesto | Messaggio di successo                |
| `method`                      | string | `POST`    | Metodo HTTP                          |
| `csrf`                        | bool   | `true`    | Includi token CSRF                   |
| `confirmation`                | bool   | `false`   | Mostra dialog di conferma            |
| `confirmMessage`              | string | `null`    | Messaggio di conferma personalizzato |
| `preserveSelection`           | bool   | `false`   | Mantieni selezioni dopo l'azione     |
| `ajax`                        | bool   | `false`   | Usa AJAX invece del submit form      |

### Gestire Azioni di Massa nel Controller

php

```
<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        $action = $request->input('action');
        
        // Esegui eliminazione di massa
        $deletedCount = Asset::whereIn('id', $ids)->delete();
        
        return response()->json([
            'message' => "{$deletedCount} asset eliminati con successo",
            'deleted_count' => $deletedCount,
            'title' => 'Successo',
        ]);
    }
    
    public function bulkStatus(Request $request)
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status', 'active');
        
        $updatedCount = Asset::whereIn('id', $ids)
            ->update(['status' => $status]);
        
        return response()->json([
            'message' => "{$updatedCount} asset aggiornati",
            'updated_count' => $updatedCount,
            'title' => 'Successo',
        ]);
    }
}
```


### Formato Risposta AJAX

Il gestore delle azioni di massa si aspetta risposte JSON:

json

```
{
    "message": "Operazione completata con successo",
    "title": "Successo",
    "deleted_count": 5,
    "updated_count": null,
    "redirectUrl": "/assets"
}
```


---

## SearchBuilder

### Abilitare SearchBuilder

php

```
public function html(): HtmlBuilder
{
    return $this->dataTableService->configureHtml(
        $this->builder(),
        'assets-table',
        [],    // customParameters
        [],    // customButtons
        false, // enableCheckboxes
        true   // enableSearchBuilder
    );
}
```


### Configurazione SearchBuilder

Il bottone SearchBuilder viene aggiunto automaticamente al layout quando abilitato:

php

```
// Configurazione SearchBuilder personalizzata
public function html(): HtmlBuilder
{
    $customParameters = [
        'searchBuilder' => [
            'depthLimit' => 2,
            'columns' => [0, 1, 2, 3], // Limita le colonne ricercabili
        ],
    ];

    return $this->dataTableService->configureHtml(
        $this->builder(),
        'assets-table',
        $customParameters,
        [],
        false,
        true
    );
}
```


---

## Riordino Righe

### Abilitare Riordino Righe

php

```
public function html(): HtmlBuilder
{
    return $this->dataTableService->configureHtml(
        $this->builder(),
        'assets-table',
        [],    // customParameters
        [],    // customButtons
        false, // enableCheckboxes
        false, // enableSearchBuilder
        [],    // checkboxOptions
        true,  // enableRowReorder
        [
            'route' => 'assets.reorder',
            'dataSrc' => 'order',
            'selector' => 'td:not(:first-child):not(:last-child)',
        ]
    );
}
```


### Gestire Riordino nel Controller

php

```
public function reorder(Request $request)
{
    $reorderData = $request->input('reorder', []);
    
    foreach ($reorderData as $item) {
        Asset::where('id', $item['id'])
            ->update([
                'order' => $item['new_position'],
            ]);
    }
    
    return response()->json([
        'message' => 'Ordine aggiornato con successo',
        'success' => true,
    ]);
}
```


### Opzioni Riordino

| OpzioneTipoDefaultDescrizione |        |                                         |                            |
| ----------------------------- | ------ | --------------------------------------- | -------------------------- |
| `route`                       | string | richiesto                               | Rotta per salvare l'ordine |
| `dataSrc`                     | string | `order`                                 | Campo sorgente dati        |
| `selector`                    | string | `td:not(:first-child):not(:last-child)` | Selettore riga             |
| `successMessage`              | string | `Ordinamento aggiornato con successo`   | Messaggio successo         |
| `errorMessage`                | string | `Errore durante l'aggiornamento`        | Messaggio errore           |

---

## Script per Colonne

### Script Click Colonna

Genera uno script per gestire i click sulle colonne:

php

```
public function html(): HtmlBuilder
{
    $columnClickScript = $this->dataTableService->generateColumnClickScript(
        'assets-table',           // ID Tabella
        3,                        // Indice colonna (1-based)
        route('assets.toggle'),   // Rotta
        'Stato aggiornato',        // Messaggio successo
        'Aggiornamento fallito'    // Messaggio errore
    );

    $customParameters = [
        'drawCallback' => $columnClickScript,
    ];

    return $this->dataTableService->configureHtml(
        $this->builder(),
        'assets-table',
        $customParameters
    );
}
```


### Script Cambio Colonna

Gestisce i cambiamenti nei dropdown delle colonne:

php

```
public function html(): HtmlBuilder
{
    $changeScript = $this->dataTableService->generateColumnChangeScript(
        'assets-table',
        4,
        route('assets.update-status'),
        'Stato aggiornato con successo',
        'Aggiornamento stato fallito'
    );

    $customParameters = [
        'drawCallback' => $changeScript,
    ];

    return $this->dataTableService->configureHtml(
        $this->builder(),
        'assets-table',
        $customParameters
    );
}
```


### Script Toggle Checkbox

php

```
public function html(): HtmlBuilder
{
    $toggleScript = $this->dataTableService->generateCheckboxToggleScript(
        'assets-table',
        [5, 6], // Indici colonne con checkbox
        route('assets.toggle-feature'),
        'Funzionalità attivata',
        'Attivazione fallita'
    );

    $customParameters = [
        'drawCallback' => $toggleScript,
    ];

    return $this->dataTableService->configureHtml(
        $this->builder(),
        'assets-table',
        $customParameters
    );
}
```


---

## Opzioni di Export

### Bottoni Export Predefiniti

Il pacchetto include automaticamente i bottoni di export:

- Excel (XLSX)
- CSV
- PDF
- Stampa
- Copia

### Configurazione Export

php

```
// Nella tua classe DataTable
protected function getColumns(): array
{
    return [
        Column::make('id')->exportable(false), // Escludi dall'export
        Column::make('name'),
        Column::make('email'),
        Column::make('created_at')->exportable(false),
    ];
}
```


### Export Righe Selezionate

Quando i checkbox sono abilitati, gli export includeranno solo le righe selezionate:

javascript

```
// Export manuale delle righe selezionate
window.performExportAction(
    'assets-table',
    'excelHtml5',
    button,
    event,
    dataTable,
    config
);
```


---

## Funzionalità Avanzate

### Draw Callback Personalizzato

php

```
public function html(): HtmlBuilder
{
    $customParameters = [
        'drawCallback' => 'function() {
            // Draw callback personalizzato
            console.log("Tabella disegnata");
            
            // Inizializza tooltip
            $("[data-bs-toggle=tooltip]").tooltip();
            
            // Gestori bottoni personalizzati
            $(".custom-action").on("click", function() {
                // Gestisci click
            });
        }',
    ];

    return $this->dataTableService->configureHtml(
        $this->builder(),
        'assets-table',
        $customParameters
    );
}
```


### Combinare Multiple Funzionalità

php

```
public function html(): HtmlBuilder
{
    // Azioni di massa
    $bulkActions = [
        [
            'text' => 'Elimina Selezionati',
            'action' => 'delete',
            'route' => 'assets.bulk-delete',
            'successMessage' => 'Asset eliminati',
            'confirmation' => true,
        ],
    ];

    $buttons = $this->dataTableService->addBulkActionButtons($bulkActions);

    // Opzioni checkbox
    $checkboxOptions = [
        'enableCounter' => true,
        'selectAllSelector' => '#select-all-assets',
    ];

    // Opzioni riordino righe
    $rowReorderOptions = [
        'route' => 'assets.reorder',
        'dataSrc' => 'order',
    ];

    // Configura con tutte le funzionalità
    return $this->dataTableService->configureHtml(
        $this->builder(),
        'assets-table',
        [],                 // customParameters
        $buttons,           // customButtons con azioni di massa
        true,               // enableCheckboxes
        true,               // enableSearchBuilder
        $checkboxOptions,   // checkboxOptions
        true,               // enableRowReorder
        $rowReorderOptions  // rowReorderOptions
    );
}
```


### Implementazione Filtri Personalizzati

php

```
public function query()
{
    $query = Asset::query();
    
    // Applica filtri SearchBuilder
    if (request()->has('searchBuilder')) {
        $query = $this->filterService->applyFilters(
            $query,
            request('searchBuilder')
        );
    }
    
    // Applica filtri personalizzati
    if (request()->filled('category')) {
        $query->where('category_id', request('category'));
    }
    
    if (request()->filled('date_range')) {
        $dates = explode(' - ', request('date_range'));
        $query->whereBetween('created_at', $dates);
    }
    
    return $query;
}
```


### Debug

php

```
// Abilita log debug per i filtri
$searchBuilder = request('searchBuilder', []);
$this->filterService->debugSearchBuilder($searchBuilder);

// Il log conterrà:
// - Logica (AND/OR)
// - Numero di criteri
// - Struttura completa dei criteri
```


---

## Best Practices

1. **Valida sempre le azioni di massa** nei controller
2. **Usa ID tabella significativi** per una migliore gestione JavaScript
3. **Implementa protezione CSRF** per tutte le richieste POST
4. **Testa con diverse lingue** per assicurarti che le traduzioni funzionino
5. **Monitora le performance** con grandi dataset
6. **Usa indici** sulle colonne filtrate frequentemente

## Risoluzione Problemi

### Problemi Comuni

1. **Checkbox non funzionano**: Assicurati che jQuery sia caricato prima di DataTables
2. **Azioni di massa non partono**: Controlla se il token CSRF è impostato correttamente
3. **Filtri data non funzionano**: Verifica che il formato data corrisponda ai formati supportati
4. **Riordino righe fallisce**: Controlla se la rotta è definita correttamente

### Modalità Debug

Abilita la modalità debug nel tuo `.env`:

env

```
APP_DEBUG=true
```


Controlla i log:

bash

```
tail -f storage/logs/laravel.log
```


---

## Esempi Completi

### Esempio Completo: Gestione Asset

php

```
<?php

namespace App\DataTables;

use App\Models\Asset;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use RobSol66\DataTableServices\Services\DataTableService;
use RobSol66\DataTableServices\Services\FilterService;

class AssetDataTable extends DataTable
{
    protected DataTableService $dataTableService;
    protected FilterService $filterService;

    public function __construct(
        DataTableService $dataTableService,
        FilterService $filterService
    ) {
        parent::__construct();
        $this->dataTableService = $dataTableService;
        $this->filterService = $filterService;
    }

    public function html(): HtmlBuilder
    {
        // Azioni di massa
        $bulkActions = [
            [
                'text' => '<i class="fas fa-trash"></i> Elimina Selezionati',
                'action' => 'delete',
                'route' => route('assets.bulk-delete'),
                'successMessage' => 'Asset eliminati con successo',
                'method' => 'POST',
                'confirmation' => true,
                'confirmMessage' => 'Sei sicuro di voler eliminare gli asset selezionati?',
            ],
            [
                'text' => '<i class="fas fa-check-circle"></i> Attiva Selezionati',
                'action' => 'activate',
                'route' => route('assets.bulk-activate'),
                'successMessage' => 'Asset attivati con successo',
                'method' => 'POST',
                'ajax' => true,
                'preserveSelection' => true,
            ],
        ];

        $buttons = $this->dataTableService->addBulkActionButtons($bulkActions);

        return $this->dataTableService->configureHtml(
            $this->builder(),
            'assets-table',
            [],
            $buttons,
            true,  // Abilita checkbox
            true,  // Abilita SearchBuilder
            [
                'enableCounter' => true,
            ],
            true,  // Abilita riordino righe
            [
                'route' => route('assets.reorder'),
                'dataSrc' => 'order',
            ]
        );
    }

    public function query()
    {
        $query = Asset::with('category', 'assignedTo');
        
        return $this->filterService->applyFilters(
            $query,
            request('searchBuilder', [])
        );
    }

    protected function getColumns(): array
    {
        return [
            Column::make('checkbox')
                ->title('<input type="checkbox" id="select-all" class="form-check-input">')
                ->orderable(false)
                ->searchable(false)
                ->exportable(false)
                ->printable(false)
                ->width('40px')
                ->addClass('text-center')
                ->render(function ($row) {
                    return '<input type="checkbox" class="row-checkbox form-check-input" value="' . $row->id . '">';
                }),
            Column::make('id')->title('ID'),
            Column::make('name')->title('Nome Asset'),
            Column::make('category.name')->title('Categoria'),
            Column::make('status')->title('Stato'),
            Column::make('assignedTo.name')->title('Assegnato A'),
            Column::make('created_at')->title('Creato Il'),
        ];
    }
}
```


### Controller Completo

php

```
<?php

namespace App\Http\Controllers;

use App\DataTables\AssetDataTable;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AssetController extends Controller
{
    public function index(AssetDataTable $dataTable)
    {
        return $dataTable->render('assets.index');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return response()->json([
                'message' => 'Nessun asset selezionato',
                'title' => 'Attenzione',
            ], 400);
        }

        try {
            $deletedCount = Asset::whereIn('id', $ids)->delete();
            
            Log::info('Bulk delete completato', [
                'user_id' => auth()->id(),
                'deleted_ids' => $ids,
                'count' => $deletedCount,
            ]);
            
            return response()->json([
                'message' => "{$deletedCount} asset eliminati con successo",
                'deleted_count' => $deletedCount,
                'title' => 'Successo',
            ]);
        } catch (\Exception $e) {
            Log::error('Errore bulk delete', [
                'error' => $e->getMessage(),
                'ids' => $ids,
            ]);
            
            return response()->json([
                'message' => 'Errore durante l\'eliminazione',
                'title' => 'Errore',
            ], 500);
        }
    }

    public function reorder(Request $request)
    {
        $reorderData = $request->input('reorder', []);
        
        try {
            foreach ($reorderData as $item) {
                Asset::where('id', $item['id'])
                    ->update(['order' => $item['new_position']]);
            }
            
            return response()->json([
                'message' => 'Ordine aggiornato con successo',
                'success' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Errore riordino', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Errore durante l\'aggiornamento dell\'ordine',
                'success' => false,
            ], 500);
        }
    }
}
```


### View Completa

blade

```
{{-- resources/views/assets/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Gestione Asset')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Asset</h3>
                        <div class="card-tools">
                            <a href="{{ route('assets.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Nuovo Asset
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="assets-table-selection-counter" style="display: none;" class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <span id="selection-count">0</span> righe selezionate
                            <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="window.forceCleanSelections('assets-table')">
                                <i class="fas fa-times"></i> Cancella Selezioni
                            </button>
                        </div>
                        
                        {!! $dataTable->table(['class' => 'table table-striped table-bordered table-hover']) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
    
    <script>
        // Aggiorna il contatore delle selezioni
        $(document).ready(function() {
            window.updateSelectionCounter = function(tableId, counterId = null) {
                var selectedCount = window.getSelectedRowIds(tableId).length;
                var counterElement = counterId ? $('#' + counterId) : $('#' + tableId + '-selection-counter');
                
                if (counterElement.length > 0) {
                    if (selectedCount > 0) {
                        counterElement.find('#selection-count').text(selectedCount);
                        counterElement.show();
                    } else {
                        counterElement.hide();
                    }
                }
                
                return selectedCount;
            };
        });
    </script>
@endpush
```


---

## Supporto

Per problemi e richieste di funzionalità, crea un issue nel repository GitHub.

## Contribuire

I contributi sono benvenuti! Leggi le linee guida per i contributi prima di inviare una pull request.

## Licenza

Licenza MIT. Vedi il file LICENSE per i dettagli.
