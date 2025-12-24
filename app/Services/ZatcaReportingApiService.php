<?php

namespace App\Services;

use App\Models\ZatcaConfiguration;
use App\Models\ZatcaInvoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ZatcaReportingApiService
{
    protected $zatcaValidationService;
    protected $zatcaSignatureService;

    // ZATCA Reporting API endpoints
    protected $endpoints = [
        'sandbox' => 'https://gw-fatoora.zatca.gov.sa/e-invoice/developer-portal/api/reporting',
        'production' => 'https://gw-fatoora.zatca.gov.sa/e-invoice/developer-portal/api/reporting',
    ];

    public function __construct(ZatcaValidationService $zatcaValidationService, ZatcaSignatureService $zatcaSignatureService)
    {
        $this->zatcaValidationService = $zatcaValidationService;
        $this->zatcaSignatureService = $zatcaSignatureService;
    }

    /**
     * Submit B2C invoice to ZATCA Reporting API
     * This is required for B2C invoices to achieve REPORTED status within 24 hours
     */
    public function submitInvoiceForReporting(ZatcaInvoice $zatcaInvoice, ZatcaConfiguration $config): array
    {
        try {
            // Step 1: Validate invoice before submission
            $validationResult = $this->zatcaValidationService->validateZatcaCompliance(
                $zatcaInvoice->xml_content, 
                $config
            );

            if (!$validationResult['overall_valid']) {
                return [
                    'success' => false,
                    'error' => 'Invoice validation failed',
                    'validation_errors' => $validationResult['errors'],
                    'status' => 'VALIDATION_FAILED',
                ];
            }

            // Step 2: Check 24-hour reporting deadline
            if ($this->isReportingDeadlineMissed($zatcaInvoice)) {
                return [
                    'success' => false,
                    'error' => '24-hour reporting deadline missed',
                    'deadline' => $zatcaInvoice->created_at->addDay()->toISOString(),
                    'status' => 'DEADLINE_MISSED',
                ];
            }

            // Step 3: Prepare request payload
            $payload = $this->prepareReportingPayload($zatcaInvoice, $config);

            // Step 4: Submit to ZATCA Reporting API
            $response = $this->submitToReportingApi($payload, $config);

            // Step 5: Process response
            $result = $this->processReportingResponse($response, $zatcaInvoice, $config);

            // Step 6: Update invoice status
            $this->updateInvoiceStatus($zatcaInvoice, $result);

            // Step 7: Archive XML if successful
            if ($result['success'] && $result['status'] === 'REPORTED') {
                $this->archiveInvoiceXml($zatcaInvoice, $config);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('ZATCA Reporting submission failed: ' . $e->getMessage(), [
                'zatca_invoice_id' => $zatcaInvoice->id,
                'config_id' => $config->id,
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'error' => 'Reporting submission error: ' . $e->getMessage(),
                'status' => 'SUBMISSION_ERROR',
            ];
        }
    }

    /**
     * Prepare payload for ZATCA Reporting API
     */
    protected function prepareReportingPayload(ZatcaInvoice $zatcaInvoice, ZatcaConfiguration $config): array
    {
        // Get the signed XML content
        $signedXml = $zatcaInvoice->xml_content;

        // Prepare headers
        $headers = $this->getReportingHeaders($config);

        // Prepare payload according to ZATCA specifications
        $payload = [
            'invoice' => base64_encode($signedXml),
            'uuid' => $zatcaInvoice->invoice_uuid,
            'invoiceHash' => $zatcaInvoice->invoice_hash,
            'previousInvoiceHash' => $zatcaInvoice->previous_hash,
            'reportingStatus' => 'REPORTED', // For B2C invoices
            'reportingDateTime' => now()->toISOString(),
        ];

        // Add invoice date for time validation
        $payload['invoiceDate'] = $zatcaInvoice->invoice->issue_date ?? now()->toDateString();

        return [
            'payload' => $payload,
            'headers' => $headers,
        ];
    }

    /**
     * Submit to ZATCA Reporting API
     */
    protected function submitToReportingApi(array $payloadData, ZatcaConfiguration $config): array
    {
        $endpoint = $this->getEndpoint($config);
        $headers = $payloadData['headers'];
        $payload = $payloadData['payload'];

        try {
            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->post($endpoint, $payload);

            return [
                'status_code' => $response->status(),
                'success' => $response->successful(),
                'body' => $response->body(),
                'json' => $response->json(),
            ];

        } catch (\Exception $e) {
            Log::error('ZATCA Reporting API request failed: ' . $e->getMessage());
            return [
                'status_code' => 0,
                'success' => false,
                'error' => $e->getMessage(),
                'body' => null,
                'json' => null,
            ];
        }
    }

    /**
     * Process ZATCA Reporting API response
     */
    protected function processReportingResponse(array $response, ZatcaInvoice $zatcaInvoice, ZatcaConfiguration $config): array
    {
        if (!$response['success']) {
            return [
                'success' => false,
                'error' => 'API request failed with status: ' . $response['status_code'],
                'response_body' => $response['body'],
                'status' => 'API_ERROR',
            ];
        }

        $responseData = $response['json'];

        // Handle successful response
        if ($response['status_code'] === 200 && isset($responseData['reportingStatus'])) {
            $reportingStatus = $responseData['reportingStatus'];
            
            return [
                'success' => true,
                'status' => $reportingStatus,
                'reporting_response' => $responseData,
                'reporting_id' => $responseData['reportingId'] ?? null,
                'reporting_timestamp' => $responseData['reportingTimestamp'] ?? now()->toISOString(),
                'message' => 'Invoice successfully reported',
            ];
        }

        // Handle error responses
        if (isset($responseData['errors'])) {
            $errors = is_array($responseData['errors']) ? $responseData['errors'] : [$responseData['errors']];
            
            return [
                'success' => false,
                'error' => 'ZATCA validation failed',
                'zatca_errors' => $errors,
                'response_data' => $responseData,
                'status' => 'VALIDATION_FAILED',
            ];
        }

        // Handle unexpected response format
        return [
            'success' => false,
            'error' => 'Unexpected response format from ZATCA',
            'response_data' => $responseData,
            'status' => 'UNEXPECTED_RESPONSE',
        ];
    }

    /**
     * Check if 24-hour reporting deadline is missed
     */
    protected function isReportingDeadlineMissed(ZatcaInvoice $zatcaInvoice): bool
    {
        $deadline = $zatcaInvoice->created_at->addDay(); // 24 hours from creation
        return now()->gt($deadline);
    }

    /**
     * Get headers for ZATCA Reporting API
     */
    protected function getReportingHeaders(ZatcaConfiguration $config): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->getAccessToken($config),
            'Invoice-Type' => 'B2C',
            'Reporting-Status' => 'REPORTED',
            'X-Reporting-Date' => now()->toISOString(),
        ];

        // Add certificate information for Phase 2
        if ($config->zatca_phase === 'phase2') {
            $headers['X-ZATCA-Certificate'] = $this->getCertificateInfo($config);
        }

        return $headers;
    }

    /**
     * Get ZATCA API access token
     */
    protected function getAccessToken(ZatcaConfiguration $config): string
    {
        // In production, implement OAuth2 token exchange
        // For now, return stored token or generate demo token
        
        if ($config->zatca_access_token && !$this->isTokenExpired($config->zatca_access_token)) {
            return $config->zatca_access_token;
        }

        // Generate new token (this would normally be done via OAuth2)
        return $this->generateAccessToken($config);
    }

    /**
     * Generate new ZATCA access token
     */
    protected function generateAccessToken(ZatcaConfiguration $config): string
    {
        // This is a placeholder implementation
        // In production, implement proper OAuth2 flow with ZATCA
        
        $tokenData = [
            'client_id' => $config->zatca_client_id,
            'client_secret' => $config->zatca_client_secret,
            'grant_type' => 'client_credentials',
            'scope' => 'invoice_reporting',
        ];

        // In production, make actual token request to ZATCA OAuth endpoint
        // $response = Http::post($tokenEndpoint, $tokenData);
        
        // For demonstration, return a mock token
        $mockToken = 'zatca_mock_reporting_token_' . time();
        
        // Update configuration with new token
        $config->zatca_access_token = $mockToken;
        $config->zatca_token_expires_at = now()->addHours(1);
        $config->save();

        return $mockToken;
    }

    /**
     * Check if access token is expired
     */
    protected function isTokenExpired(?string $token): bool
    {
        if (!$token) {
            return true;
        }

        // For demo tokens, assume they're valid for 1 hour
        return false; // In production, check actual expiration time
    }

    /**
     * Get certificate information for Phase 2
     */
    protected function getCertificateInfo(ZatcaConfiguration $config): string
    {
        $certificatePath = storage_path("zatca/certificates/{$config->id}_certificate.pem");
        
        if (file_exists($certificatePath)) {
            $certificate = file_get_contents($certificatePath);
            return base64_encode($certificate);
        }

        return '';
    }

    /**
     * Get API endpoint based on environment
     */
    protected function getEndpoint(ZatcaConfiguration $config): string
    {
        $environment = $config->zatca_environment ?? 'sandbox';
        return $this->endpoints[$environment] ?? $this->endpoints['sandbox'];
    }

    /**
     * Update invoice status based on reporting result
     */
    protected function updateInvoiceStatus(ZatcaInvoice $zatcaInvoice, array $result): void
    {
        $zatcaInvoice->zatca_status = $result['status'];
        $zatcaInvoice->zatca_response = json_encode($result);
        $zatcaInvoice->reporting_timestamp = $result['reporting_timestamp'] ?? now();
        $zatcaInvoice->save();

        Log::info('ZATCA invoice reporting status updated', [
            'zatca_invoice_id' => $zatcaInvoice->id,
            'status' => $result['status'],
            'success' => $result['success'],
        ]);
    }

    /**
     * Archive invoice XML for compliance
     */
    protected function archiveInvoiceXml(ZatcaInvoice $zatcaInvoice, ZatcaConfiguration $config): void
    {
        try {
            $archivePath = storage_path("zatca/reported/{$zatcaInvoice->company_id}");
            
            if (!file_exists($archivePath)) {
                mkdir($archivePath, 0755, true);
            }

            $filename = "invoice_{$zatcaInvoice->invoice_uuid}_reported_" . date('Y-m-d_H-i-s') . '.xml';
            $filepath = $archivePath . '/' . $filename;

            file_put_contents($filepath, $zatcaInvoice->xml_content);

            Log::info('ZATCA invoice XML archived', [
                'zatca_invoice_id' => $zatcaInvoice->id,
                'archive_path' => $filepath,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to archive ZATCA invoice XML: ' . $e->getMessage());
        }
    }

    /**
     * Get reporting status for an invoice
     */
    public function getReportingStatus(ZatcaInvoice $zatcaInvoice, ZatcaConfiguration $config): array
    {
        try {
            $endpoint = $this->getEndpoint($config) . '/' . $zatcaInvoice->invoice_uuid;
            $headers = $this->getReportingHeaders($config);

            $response = Http::withHeaders($headers)->get($endpoint);

            if ($response->successful()) {
                $statusData = $response->json();
                
                return [
                    'success' => true,
                    'status' => $statusData['reportingStatus'] ?? 'UNKNOWN',
                    'data' => $statusData,
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get reporting status',
                'status_code' => $response->status(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error getting reporting status: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Batch submit multiple invoices for reporting
     */
    public function batchSubmitForReporting(array $zatcaInvoiceIds, ZatcaConfiguration $config): array
    {
        $results = [];
        $zatcaInvoices = ZatcaInvoice::whereIn('id', $zatcaInvoiceIds)->get();

        foreach ($zatcaInvoices as $zatcaInvoice) {
            $result = $this->submitInvoiceForReporting($zatcaInvoice, $config);
            $results[$zatcaInvoice->id] = $result;
            
            // Add delay between requests to avoid rate limiting
            sleep(1);
        }

        return [
            'success' => true,
            'results' => $results,
            'total_processed' => count($results),
            'successful' => count(array_filter($results, function($r) { return $r['success']; })),
        ];
    }

    /**
     * Auto-report due invoices (cron job)
     * This should be called by a scheduled task
     */
    public function autoReportDueInvoices(ZatcaConfiguration $config): array
    {
        try {
            // Get invoices that need reporting (within 24 hours)
            $dueInvoices = ZatcaInvoice::where('zatca_status', 'PENDING')
                ->where('company_id', $config->company_id)
                ->where('created_at', '>=', now()->subDay()) // Within last 24 hours
                ->get();

            $results = [];
            $processed = 0;
            $successful = 0;

            foreach ($dueInvoices as $zatcaInvoice) {
                $result = $this->submitInvoiceForReporting($zatcaInvoice, $config);
                $results[$zatcaInvoice->id] = $result;
                
                if ($result['success']) {
                    $successful++;
                }
                
                $processed++;
                
                // Add delay between requests
                sleep(2);
            }

            Log::info('ZATCA auto-report completed', [
                'config_id' => $config->id,
                'processed' => $processed,
                'successful' => $successful,
            ]);

            return [
                'success' => true,
                'results' => $results,
                'total_processed' => $processed,
                'successful' => $successful,
                'failed' => $processed - $successful,
            ];

        } catch (\Exception $e) {
            Log::error('ZATCA auto-report failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get reporting compliance report
     */
    public function getReportingComplianceReport(ZatcaConfiguration $config, Carbon $startDate, Carbon $endDate): array
    {
        try {
            $invoices = ZatcaInvoice::where('company_id', $config->company_id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $report = [
                'total_invoices' => $invoices->count(),
                'reported' => $invoices->where('zatca_status', 'REPORTED')->count(),
                'pending' => $invoices->where('zatca_status', 'PENDING')->count(),
                'failed' => $invoices->where('zatca_status', 'FAILED')->count(),
                'deadline_missed' => 0,
                'compliance_rate' => 0,
                'invoices' => [],
            ];

            // Check for deadline missed invoices
            foreach ($invoices as $invoice) {
                if ($invoice->zatca_status === 'PENDING' && $this->isReportingDeadlineMissed($invoice)) {
                    $report['deadline_missed']++;
                }
                
                $report['invoices'][] = [
                    'id' => $invoice->id,
                    'uuid' => $invoice->invoice_uuid,
                    'status' => $invoice->zatca_status,
                    'created_at' => $invoice->created_at->toISOString(),
                    'deadline_missed' => $invoice->zatca_status === 'PENDING' && $this->isReportingDeadlineMissed($invoice),
                ];
            }

            // Calculate compliance rate
            $report['compliance_rate'] = $report['total_invoices'] > 0 
                ? round(($report['reported'] / $report['total_invoices']) * 100, 2)
                : 0;

            return $report;

        } catch (\Exception $e) {
            Log::error('ZATCA Reporting compliance report failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
