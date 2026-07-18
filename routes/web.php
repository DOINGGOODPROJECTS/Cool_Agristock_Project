<?php

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\DetailController;
use App\Http\Controllers\RottenController;
use App\Http\Controllers\TariffController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReleaseController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\CapacityController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\TemperatureController;
use App\Http\Controllers\InventoryOpController;
use App\Http\Controllers\SyncAuditLogController;
use App\Http\Controllers\SyncSessionController;
use App\Http\Controllers\MemberPhoneController;

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

// Route::get('/', [Controller::class, 'welcome'])->name('welcome');
Route::get('/{id}/invoice', [PdfController::class, 'invoice'])->name('stocks.invoice');
// Route::get('/inklyhq.com/l/26CIJ', [Controller::class, 'handleClick']);

Route::get('/login/google', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('/login/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
Route::get('/inklyhq.com/l/26CIJ', [GoogleAuthController::class, 'accessDrive']);

// Locale switching route
Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::middleware(['auth', 'locale'])->group(function () {
    Route::get('/profile', [Controller::class, 'profile'])->name('profile');
    Route::post('/dashboard/content', [Controller::class, 'getDashboardContent']);    
    Route::get('/{locale}/update', [Controller::class, 'setLocaleUpdate'])->name('locale.update');
    Route::get('/dashboard', [Controller::class, 'dashboard'])->middleware('verified')->name('dashboard');    
    Route::get('/customers', [UserController::class, 'customers'])->middleware(['admin.supervisor.accountant'])->name('customers');
    

    // ── Sync layer ────────────────────────────────────────────────────
    Route::get('/inventory-ops', [InventoryOpController::class, 'index'])
        ->middleware('sync.permission:sync.pull')
        ->name('inventory-ops.index');

    Route::post('/inventory-ops/{opId}/accept',  [InventoryOpController::class, 'accept'])
        ->middleware('sync.permission:sync.accept')
        ->name('inventory-ops.accept');

    Route::post('/inventory-ops/{opId}/discard', [InventoryOpController::class, 'discard'])
        ->middleware('sync.permission:sync.discard')
        ->name('inventory-ops.discard');

    Route::post('/inventory-ops/{opId}/cancel',  [InventoryOpController::class, 'cancel'])
        ->middleware('sync.permission:sync.cancel')
        ->name('inventory-ops.cancel');

    Route::post('/inventory-ops/merge',           [InventoryOpController::class, 'merge'])
        ->middleware('sync.permission:sync.merge')
        ->name('inventory-ops.merge');

    Route::put('/inventory-ops/{opId}',          [InventoryOpController::class, 'edit'])
        ->middleware('sync.permission:sync.edit')
        ->name('inventory-ops.edit');

    Route::post('/inventory-ops/{opId}/override', [InventoryOpController::class, 'override'])
        ->middleware('sync.permission:sync.accept')
        ->name('inventory-ops.override');

    Route::get('/sync-audit-log',                    [SyncAuditLogController::class, 'index'])
        ->middleware('sync.permission:log.view')
        ->name('sync-audit-log.index');

    Route::get('/sync-audit-log/export',             [SyncAuditLogController::class, 'export'])
        ->middleware('sync.permission:log.export')
        ->name('sync-audit-log.export');

    Route::get('/inventory-ops/{opId}/history',      [SyncAuditLogController::class, 'forOp'])
        ->middleware('sync.permission:sync.pull')
        ->name('inventory-ops.history');

    Route::get('/sync-sessions', [SyncSessionController::class, 'index'])
        ->middleware('sync.permission:sync.reconcile')
        ->name('sync-sessions.index');

    Route::get('/sync-protocol', fn() => view('admin.sync-protocol'))->name('sync-protocol');

    Route::get('/member-phones',                       [MemberPhoneController::class, 'index'])->name('member-phones.index');
    Route::post('/member-phones',                      [MemberPhoneController::class, 'store'])->name('member-phones.store');
    Route::post('/member-phones/{id}/verify',          [MemberPhoneController::class, 'verify'])->name('member-phones.verify');
    Route::delete('/member-phones/{id}',               [MemberPhoneController::class, 'destroy'])->name('member-phones.destroy');
    // ─────────────────────────────────────────────────────────────────

    Route::resource('stocks', StockController::class);
    Route::resource('details', DetailController::class);
    Route::resource('rottens', RottenController::class);
    Route::resource('tariffs', TariffController::class);
    Route::resource('payments', PaymentController::class);
    Route::resource('billings', BillingController::class);
    Route::resource('releases', ReleaseController::class);
    Route::resource('claims', ClaimController::class);

    Route::middleware('admin')->group(function () {
        Route::get('/exports/individual', [ExportController::class, 'index'])->name('exports.individual');
        Route::post('/exports/individual', [ExportController::class, 'export'])->name('exports.individual.export');
        Route::get('/exports/all', [ExportController::class, 'exportAll'])->name('exports.all');
    });

    // ── Accounting ────────────────────────────────────────────────────────────
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::get('/invoices',                [\App\Http\Controllers\Accounting\InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/create',         [\App\Http\Controllers\Accounting\InvoiceController::class, 'create'])->name('invoices.create');
        Route::post('/invoices',               [\App\Http\Controllers\Accounting\InvoiceController::class, 'storeManual'])->name('invoices.store');
        Route::get('/invoices/{id}',           [\App\Http\Controllers\Accounting\InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/invoices/{id}/edit',       [\App\Http\Controllers\Accounting\InvoiceController::class, 'edit'])->name('invoices.edit');
        Route::put('/invoices/{id}',           [\App\Http\Controllers\Accounting\InvoiceController::class, 'update'])->name('invoices.update');
        Route::get('/invoices/{id}/pdf',       [\App\Http\Controllers\Accounting\InvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::delete('/invoices/{id}',        [\App\Http\Controllers\Accounting\InvoiceController::class, 'destroy'])->name('invoices.destroy');
        Route::get('/invoices/{id}/line/{lineId}/pdf', [\App\Http\Controllers\Accounting\InvoiceController::class, 'linePdf'])->name('invoices.line-pdf');
        Route::post('/invoices/generate',      [\App\Http\Controllers\Accounting\InvoiceController::class, 'generate'])->name('invoices.generate');
        Route::post('/invoices/process-line',  [\App\Http\Controllers\Accounting\InvoiceController::class, 'processLine'])->name('invoices.process-line');

        Route::get('/journal',               [\App\Http\Controllers\Accounting\JournalController::class, 'index'])->name('journal.index');
        Route::get('/journal/ledger',        [\App\Http\Controllers\Accounting\JournalController::class, 'ledger'])->name('journal.ledger');
        Route::get('/journal/create',        [\App\Http\Controllers\Accounting\JournalController::class, 'create'])->name('journal.create');
        Route::post('/journal',              [\App\Http\Controllers\Accounting\JournalController::class, 'store'])->name('journal.store');
        Route::post('/journal/process',      [\App\Http\Controllers\Accounting\JournalController::class, 'processEntry'])->name('journal.process');
        Route::post('/journal/process-line', [\App\Http\Controllers\Accounting\JournalController::class, 'processLine'])->name('journal.process-line');
        Route::get('/journal/{id}',          [\App\Http\Controllers\Accounting\JournalController::class, 'show'])->name('journal.show');
        Route::get('/journal/{id}/edit',     [\App\Http\Controllers\Accounting\JournalController::class, 'edit'])->name('journal.edit');
        Route::put('/journal/{id}',          [\App\Http\Controllers\Accounting\JournalController::class, 'update'])->name('journal.update');
        Route::post('/journal/{id}/submit',  [\App\Http\Controllers\Accounting\JournalController::class, 'submit'])->name('journal.submit');
        Route::delete('/journal/{id}',       [\App\Http\Controllers\Accounting\JournalController::class, 'destroy'])->name('journal.destroy');

        Route::middleware('admin')->group(function () {
            Route::post('/journal/{id}/approve',      [\App\Http\Controllers\Accounting\JournalController::class, 'approve'])->name('journal.approve');
            Route::post('/journal/{id}/reject',       [\App\Http\Controllers\Accounting\JournalController::class, 'reject'])->name('journal.reject');
            Route::post('/journal/{id}/approve-odoo', [\App\Http\Controllers\Accounting\JournalController::class, 'approveOdoo'])->name('journal.approve-odoo');
            Route::post('/journal/{id}/reject-odoo',  [\App\Http\Controllers\Accounting\JournalController::class, 'rejectOdoo'])->name('journal.reject-odoo');
        });
    });
    // ─────────────────────────────────────────────────────────────────────────

    Route::middleware('supervisor')->group(function () {
        Route::get('/incidents/{status}/{id}', [IncidentController::class, 'setStatus'])->name('incidents.status');

        Route::resource('temperatures', TemperatureController::class);
        Route::resource('capacities', CapacityController::class);
        Route::resource('categories', CategoryController::class);
        Route::resource('storages', StorageController::class);
        Route::resource('products', ProductController::class);
        Route::resource('incidents', IncidentController::class);

        Route::middleware('admin')->group(function () {
            Route::get('/groups', [Controller::class, 'groups'])->name('groups');
            Route::get('/cities', [Controller::class, 'cities'])->name('cities');

            Route::resource('users', UserController::class);
        });
    });   
});

// ── Africa's Talking SMS webhook ─────────────────────────────────────────
// No auth / CSRF — AT posts here directly. Signature verified in controller.
Route::post('/webhook/sms', \App\Http\Controllers\Webhook\SmsController::class)
    ->name('webhook.sms');

require __DIR__.'/auth.php';
