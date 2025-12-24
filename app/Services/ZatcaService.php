<?php

namespace App\Services;

use App\Models\ZatcaConfiguration;
use App\Models\ZatcaInvoice;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ZatcaService
{
    protected $zatcaApiService;
    protected $zatcaTaxService;
    protected $zatcaInvoiceService;

    public function __construct(
        ZatcaApiService $zatcaApiService,
        ZatcaTaxService $zatcaTaxService,
        ZatcaInvoiceService $zatcaInvoiceService
    ) {
        $this->zatcaApiService = $zatcaApiService;
        $this->zatcaTaxService = $zatcaTaxService;
        $this->zatcaInvoiceService = $zatcaInvoiceService;
    }

    /**
     * Check if ZATCA is enabled for a company
     */
    public function isEnabled($companyId)
    {
        $config = ZatcaConfiguration::where('company_id', $companyId)->first();
        return $config && $config->isEnabled();
    }

    /**
     * Get ZATCA configuration for a company
     */
    public function getConfiguration($companyId)
    {
        return ZatcaConfiguration::where('company_id', $companyId)->first();
    }

    /**
     * Create or update ZATCA configuration
     */
    public function updateConfiguration($companyId, array $data)
    {
        try {
            $config = ZatcaConfiguration::updateOrCreate(
                ['company_id' => $companyId],
                $data
            );

            Log::info("ZATCA configuration updated for company {$companyId}");
            return $config;
        } catch (\Exception $e) {
            Log::error("Error updating ZATCA configuration: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate ZATCA invoice from regular invoice
     */
    public function generateZatcaInvoice(Invoice $invoice)
    {
        try {
            $config = $this->getConfiguration($invoice->created_by);
            
            if (!$config || !$config->isEnabled()) {
                throw new \Exception('ZATCA is not enabled for this company');
            }

            // Generate ZATCA invoice data
            $zatcaData = $this->zatcaInvoiceService->prepareZatcaInvoiceData($invoice, $config);
            
            // Create ZATCA invoice record
            $zatcaInvoice = ZatcaInvoice::create([
                'company_id' => $invoice->created_by,
                'invoice_id' => $invoice->id,
                'zatca_uuid' => $zatcaData['uuid'],
                'zatca_invoice_number' => $zatcaData['invoice_number'],
                'zatca_invoice_type' => $zatcaData['invoice_type'],
                'zatca_data' => $zatcaData,
                'zatca_status' => 'draft',
            ]);

            // Generate QR code if Phase 2
            if ($config->getPhase() === 'phase2') {
                $qrCode = $this->zatcaInvoiceService->generateQRCode($zatcaData);
                $zatcaInvoice->update(['zatca_qr_code' => $qrCode]);
            }

            Log::info("ZATCA invoice generated for invoice ID: {$invoice->id}");
            return $zatcaInvoice;

        } catch (\Exception $e) {
            Log::error("Error generating ZATCA invoice: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Submit invoice to ZATCA for validation
     */
    public function submitToZatca(ZatcaInvoice $zatcaInvoice)
    {
        try {
            $config = $this->getConfiguration($zatcaInvoice->company_id);
            
            if (!$config || !$config->isEnabled()) {
                throw new \Exception('ZATCA is not enabled for this company');
            }

            // Generate digital signature if Phase 2
            if ($config->getPhase() === 'phase2') {
                $signature = $this->zatcaInvoiceService->generateDigitalSignature($zatcaInvoice->zatca_data, $config);
                $zatcaInvoice->update(['zatca_digital_signature' => $signature]);
            }

            // Submit to ZATCA API
            $response = $this->zatcaApiService->submitInvoice($zatcaInvoice->zatca_data, $config);
            
            $zatcaInvoice->update([
                'zatca_response' => $response,
                'zatca_status' => $response['status'] ?? 'invalid',
                'zatca_submitted_at' => now(),
                'zatca_error_message' => $response['error'] ?? null,
            ]);

            if ($response['status'] === 'valid') {
                $zatcaInvoice->update(['zatca_validated_at' => now()]);
            }

            Log::info("ZATCA invoice submitted: {$zatcaInvoice->zatca_uuid}");
            return $zatcaInvoice;

        } catch (\Exception $e) {
            Log::error("Error submitting to ZATCA: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get ZATCA invoices for a company
     */
    public function getZatcaInvoices($companyId, $limit = 50)
    {
        return ZatcaInvoice::where('company_id', $companyId)
            ->with('invoice')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get ZATCA tax report for Saudi Arabia
     */
    public function getTaxReport($companyId, $startDate, $endDate)
    {
        $config = $this->getConfiguration($companyId);
        
        if (!$config || !$config->isEnabled()) {
            throw new \Exception('ZATCA is not enabled for this company');
        }

        return $this->zatcaTaxService->generateTaxReport($companyId, $startDate, $endDate);
    }

    /**
     * Validate ZATCA configuration
     */
    public function validateConfiguration(ZatcaConfiguration $config)
    {
        $errors = [];

        if (empty($config->zatca_tax_number)) {
            $errors[] = 'Tax number is required';
        }

        if ($config->zatca_phase === 'phase2') {
            if (empty($config->zatca_certificate_path) || empty($config->zatca_private_key_path)) {
                $errors[] = 'Certificate and private key are required for Phase 2';
            }

            if (empty($config->zatca_device_id)) {
                $errors[] = 'Device ID is required for Phase 2';
            }
        }

        if ($config->zatca_phase === 'phase1' && empty($config->zatca_api_endpoint)) {
            $errors[] = 'API endpoint is required for Phase 1';
        }

        return $errors;
    }
}
