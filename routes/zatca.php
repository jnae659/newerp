<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ZatcaController;

/*
|--------------------------------------------------------------------------
| ZATCA Routes - Saudi Arabia Only
|--------------------------------------------------------------------------
| These routes are only available for companies registered in Saudi Arabia.
| All routes require authentication and company validation.
*/

// ZATCA Configuration Routes
Route::middleware(['auth', 'company'])->group(function () {
    // Only allow access for Saudi Arabia companies
    Route::middleware(['saudi-only'])->group(function () {
        
        // ZATCA Configuration
        Route::get('/zatca/configuration', [ZatcaController::class, 'index'])
            ->name('zatca.configuration');
        
        Route::post('/zatca/configuration', [ZatcaController::class, 'updateConfiguration'])
            ->name('zatca.configuration.update');

        // ZATCA API Routes
        Route::prefix('zatca/api')->name('zatca.api.')->group(function () {
            
            // Configuration Management
            Route::post('/test-connection', [ZatcaController::class, 'testConnection'])
                ->name('test-connection');
            
            Route::post('/validate-configuration', [ZatcaController::class, 'validateConfiguration'])
                ->name('validate-configuration');
            
            Route::get('/statistics', [ZatcaController::class, 'getStatistics'])
                ->name('statistics');

            // Invoice Management
            Route::post('/invoices/generate/{invoiceId}', [ZatcaController::class, 'generateZatcaInvoice'])
                ->name('invoices.generate');
            
            Route::post('/invoices/submit/{zatcaInvoiceId}', [ZatcaController::class, 'submitZatcaInvoice'])
                ->name('invoices.submit');
            
            Route::get('/invoices', [ZatcaController::class, 'listZatcaInvoices'])
                ->name('invoices.list');

            // Tax Reporting
            Route::post('/reports/tax', [ZatcaController::class, 'getTaxReport'])
                ->name('reports.tax');
            
            Route::post('/reports/vat-return', [ZatcaController::class, 'generateVATReturn'])
                ->name('reports.vat-return');
        });
    });
});
