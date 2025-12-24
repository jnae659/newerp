<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

// ZATCA Services
use App\Services\ZatcaService;
use App\Services\ZatcaApiService;
use App\Services\ZatcaTaxService;
use App\Services\ZatcaInvoiceService;
use App\Services\ZatcaUblGeneratorService;
use App\Services\ZatcaHashService;
use App\Services\ZatcaSignatureService;
use App\Services\ZatcaValidationService;
use App\Services\ZatcaQrCodeService;
use App\Services\ZatcaClearanceApiService;
use App\Services\ZatcaReportingApiService;
use App\Services\ZatcaComplianceService;
use App\Services\ZatcaEvidenceService;
use App\Services\ZatcaAuthService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register ZATCA Services
        $this->app->singleton(ZatcaService::class, function ($app) {
            return new ZatcaService(
                $app->make(ZatcaApiService::class),
                $app->make(ZatcaTaxService::class),
                $app->make(ZatcaInvoiceService::class),
                $app->make(ZatcaUblGeneratorService::class),
                $app->make(ZatcaHashService::class),
                $app->make(ZatcaSignatureService::class),
                $app->make(ZatcaValidationService::class),
                $app->make(ZatcaQrCodeService::class),
                $app->make(ZatcaClearanceApiService::class),
                $app->make(ZatcaReportingApiService::class),
                $app->make(ZatcaComplianceService::class),
                $app->make(ZatcaEvidenceService::class),
                $app->make(ZatcaAuthService::class)
            );
        });

        $this->app->singleton(ZatcaApiService::class);
        $this->app->singleton(ZatcaTaxService::class);
        $this->app->singleton(ZatcaInvoiceService::class);
        $this->app->singleton(ZatcaUblGeneratorService::class);
        $this->app->singleton(ZatcaHashService::class);
        $this->app->singleton(ZatcaSignatureService::class);
        $this->app->singleton(ZatcaValidationService::class);
        $this->app->singleton(ZatcaQrCodeService::class);
        $this->app->singleton(ZatcaClearanceApiService::class);
        $this->app->singleton(ZatcaReportingApiService::class);
        $this->app->singleton(ZatcaComplianceService::class);
        $this->app->singleton(ZatcaEvidenceService::class);
        $this->app->singleton(ZatcaAuthService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
