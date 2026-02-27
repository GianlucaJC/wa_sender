<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\TemplateController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Rotta per mostrare il form di creazione campagna (associata al metodo create)
Route::get('/', [CampaignController::class, 'create'])->name('campaigns.create');

// Rotta per visualizzare lo storico delle campagne
Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');

// Rotta per salvare i dati della campagna (associata al metodo store)
Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');

// Rotta per lo step 2 della creazione campagna (anteprima/mapping)
Route::get('/campaigns/step2', [CampaignController::class, 'step2'])->name('campaigns.step2');

// Rotta per validare il file dei destinatari
Route::post('/campaigns/validate', [CampaignController::class, 'validateFile'])->name('campaigns.validate');

// Rotta per avviare la campagna dopo la validazione
Route::post('/campaigns/launch', [CampaignController::class, 'launchCampaign'])->name('campaigns.launch');

// Rotta unificata per l'avvio della campagna
Route::post('/campaigns/launch-unified', [CampaignController::class, 'launchUnified'])->name('campaigns.launch.unified');

// Nuove rotte AJAX per il flusso di upload da file
Route::post('/campaigns/ajax-upload', [CampaignController::class, 'ajaxUpload'])->name('campaigns.ajax.upload');
Route::post('/campaigns/ajax-validate', [CampaignController::class, 'ajaxValidate'])->name('campaigns.ajax.validate');

// Rotte per il monitoraggio della campagna
Route::get('/campaigns/{campaign}/progress', [CampaignController::class, 'showProgress'])->name('campaigns.progress');
Route::get('/campaigns/{campaign}/status', [CampaignController::class, 'getStatus'])->name('campaigns.status');

// Rotta per la documentazione
Route::get('/docs', [CampaignController::class, 'showDocs'])->name('docs.index');

// Rotta per l'informativa sulla privacy
Route::get('/privacy-policy', [CampaignController::class, 'showPrivacyPolicy'])->name('privacy.policy');

// Rotta per l'invio del messaggio di test (chiamata via Fetch API)
Route::post('/campaigns/send-test', [CampaignController::class, 'sendTest'])->name('campaigns.sendTest');

// Rotte per la gestione dei template (sezione admin)
Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
Route::get('/templates/create', [TemplateController::class, 'create'])->name('templates.create');
Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');

// --- Gestione Account WhatsApp (Multi-cliente) ---
Route::resource('whatsapp-accounts', \App\Http\Controllers\WhatsappAccountController::class)
    ->except(['show', 'edit', 'update'])
    ->names('whatsapp-accounts');
