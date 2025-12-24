<?php

namespace App\Http\Controllers;

use App\Services\ZatcaService;
use App\Services\ZatcaApiService;
use App\Services\ZatcaTaxService;
use App\Models\ZatcaConfiguration;
use App\Models\ZatcaInvoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ZatcaController extends Controller
{
    protected $zatcaService;
    protected $zatcaApiService;
    protected $zatcaTaxService;

    public function __construct(
        ZatcaService $zatcaService,
        ZatcaApiService $zatcaApiService,
        ZatcaTaxService $zatcaTaxService
    ) {
        $this->zatcaService = $zatcaService;
        $this->zatcaApiService = $zatcaApiService;
        $this->zatcaTaxService = $zatcaTaxService;
    }

    /**
     * Show ZATCA configuration page (only for Saudi Arabia)
     */
    public function index()
    {
        $companyId = Auth::user()->id;
        $company = Auth::user();

        // Only allow access for Saudi Arabia
        if ($company->country !== 'SA') {
            return redirect()->back()->with('error', 'ZATCA integration is only available for Saudi Arabia.');
        }

        $zatcaConfig = $this->zatcaService->getConfiguration($companyId);
        
        return view('zatca.configuration', compact('zatcaConfig'));
    }

    /**
     * Update ZATCA configuration
     */
    public function updateConfiguration(Request $request)
    {
        $companyId = Auth::user()->id;
        $company = Auth::user();

        // Only allow for Saudi Arabia
        if ($company->country !== 'SA') {
            return redirect()->back()->with('error', 'ZATCA integration is only available for Saudi Arabia.');
        }

        $request->validate([
            'zatca_enabled' => 'required|in:on,off',
            'zatca_phase' => 'required|in:phase1,phase2',
            'zatca_tax_number' => 'required_if:zatca_enabled,on',
            'zatca_branch_code' => 'required_if:zatca_enabled,on',
            'zatca_api_endpoint' => 'required_if:zatca_phase,phase1',
            'zatca_api_key' => 'required_if:zatca_enabled,on',
            'zatca_api_secret' => 'required_if:zatca_enabled,on',
            'zatca_device_id' => 'required_if:zatca_phase,phase2',
            'zatca_certificate_path' => 'required_if:zatca_phase,phase2',
            'zatca_private_key_path' => 'required_if:zatca_phase,phase2',
        ]);

        try {
            $data = $request->all();
            $config = $this->zatcaService->updateConfiguration($companyId, $data);

            return redirect()->route('zatca.configuration')->with('success', 'ZATCA configuration updated successfully.');

        } catch (\Exception $e) {
            Log::error('ZATCA configuration update error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update ZATCA configuration: ' . $e->getMessage());
        }
    }

    /**
     * Test ZATCA API connection
     */
    public function testConnection(Request $request): JsonResponse
    {
        $companyId = Auth::user()->id;
        $config = $this->zatcaService->getConfiguration($companyId);

        if (!$config) {
            return response()->json(['error' => 'ZATCA configuration not found'], 404);
        }

        try {
            $result = $this->zatcaApiService->testConnection($config);
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Connection test successful' : 'Connection test failed',
                'details' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Connection test failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate ZATCA invoice from regular invoice
     */
    public function generateZatcaInvoice(Request $request, $invoiceId): JsonResponse
    {
        $companyId = Auth::user()->id;

        try {
            $invoice = \App\Models\Invoice::where('id', $invoiceId)
                ->where('created_by', $companyId)
                ->first();

            if (!$invoice) {
                return response()->json(['error' => 'Invoice not found'], 404);
            }

            // Check if ZATCA is enabled for this company
            if (!$this->zatcaService->isEnabled($companyId)) {
                return response()->json(['error' => 'ZATCA is not enabled for this company'], 400);
            }

            $zatcaInvoice = $this->zatcaService->generateZatcaInvoice($invoice);

            return response()->json([
                'success' => true,
                'zatca_invoice' => $zatcaInvoice,
                'message' => 'ZATCA invoice generated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('ZATCA invoice generation error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate ZATCA invoice: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Submit ZATCA invoice for validation
     */
    public function submitZatcaInvoice(Request $request, $zatcaInvoiceId): JsonResponse
    {
        $companyId = Auth::user()->id;

        try {
            $zatcaInvoice = ZatcaInvoice::where('id', $zatcaInvoiceId)
                ->where('company_id', $companyId)
                ->first();

            if (!$zatcaInvoice) {
                return response()->json(['error' => 'ZATCA invoice not found'], 404);
            }

            $result = $this->zatcaService->submitToZatca($zatcaInvoice);

            return response()->json([
                'success' => true,
                'zatca_invoice' => $result,
                'message' => $result->zatca_status === 'valid' ? 'Invoice validated successfully' : 'Invoice submission failed',
            ]);

        } catch (\Exception $e) {
            Log::error('ZATCA invoice submission error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to submit ZATCA invoice: ' . $e->getMessage()], 500);
        }
    }

    /**
     * List ZATCA invoices
     */
    public function listZatcaInvoices(Request $request): JsonResponse
    {
        $companyId = Auth::user()->id;
        $limit = $request->input('limit', 50);

        try {
            $invoices = $this->zatcaService->getZatcaInvoices($companyId, $limit);
            
            return response()->json([
                'success' => true,
                'invoices' => $invoices,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch ZATCA invoices: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get tax report for ZATCA
     */
    public function getTaxReport(Request $request): JsonResponse
    {
        $companyId = Auth::user()->id;

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        try {
            $report = $this->zatcaService->getTaxReport(
                $companyId,
                $request->start_date,
                $request->end_date
            );

            return response()->json([
                'success' => true,
                'report' => $report,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate tax report: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate VAT return for ZATCA
     */
    public function generateVATReturn(Request $request): JsonResponse
    {
        $companyId = Auth::user()->id;

        $request->validate([
            'tax_period' => 'required|string|regex:/^\d{4}-\d{2}$/', // YYYY-MM format
        ]);

        try {
            $vatReturn = $this->zatcaTaxService->generateVATReturn($companyId, $request->tax_period);

            return response()->json([
                'success' => true,
                'vat_return' => $vatReturn,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate VAT return: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Validate ZATCA configuration
     */
    public function validateConfiguration(Request $request): JsonResponse
    {
        $companyId = Auth::user()->id;
        $config = $this->zatcaService->getConfiguration($companyId);

        if (!$config) {
            return response()->json(['error' => 'ZATCA configuration not found'], 404);
        }

        try {
            $errors = $this->zatcaService->validateConfiguration($config);
            $apiErrors = $this->zatcaApiService->validateCredentials($config);
            $taxErrors = $this->zatcaTaxService->validateTaxConfiguration($config);

            $allErrors = array_merge($errors, $apiErrors, $taxErrors);

            return response()->json([
                'success' => empty($allErrors),
                'errors' => $allErrors,
                'is_valid' => empty($allErrors),
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to validate configuration: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get ZATCA statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $companyId = Auth::user()->id;

        try {
            $invoices = ZatcaInvoice::where('company_id', $companyId)->get();
            
            $stats = [
                'total_invoices' => $invoices->count(),
                'valid_invoices' => $invoices->where('zatca_status', 'valid')->count(),
                'invalid_invoices' => $invoices->where('zatca_status', 'invalid')->count(),
                'pending_invoices' => $invoices->where('zatca_status', 'draft')->count(),
                'recent_submissions' => $invoices->where('zatca_submitted_at', '>=', now()->subDays(30))->count(),
            ];

            return response()->json([
                'success' => true,
                'statistics' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch statistics: ' . $e->getMessage()], 500);
        }
    }
}
