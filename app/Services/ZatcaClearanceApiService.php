<?php

namespace App\Services;

use App\Models\ZatcaConfiguration;
use App\Models\ZatcaInvoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ZatcaClearanceApiService
{
    protected $zatcaValidationService;
    protected $zatcaSignatureService;

    // ZATCA Clearance API endpoints
    protected $endpoints = [
        'sandbox' => 'https://gw-fatoora.zatca.gov.sa/e-invoice/developer-portal/api/clearance',
        'production' => 'https://gw-fatoora.zatca.gov.sa/e-invoice/developer-portal/api/clearance',
    ];

    public function __construct(ZatcaValidationService $zatcaValidationService, ZatcaSignatureService $zatcaSignatureService)
    {
        $this->zatcaValidationService = $zatcaValidationService;
        $this->zatcaSignatureService = $zatcaSignatureService;
    }

    /**
     * Submit B2B invoice to ZATCA Clearance API
     * This is required for B2B invoices to achieve CLEARED status
     */
    public function submitInvoiceForClearance(ZatcaInvoice $zatcaInvoice, ZatcaConfiguration $config): array
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

            // Step 2: Prepare request payload
            $payload = $this->prepareClearancePayload($zatcaInvoice, $config);

            // Step 3: Submit to ZATCA Clearance API
            $response = $this->submitToClearanceApi($payload, $config);

            // Step 4: Process response
            $result = $this->processClearanceResponse($response, $zatcaInvoice, $config);

            // Step 5: Update invoice status
            $this->updateInvoiceStatus($zatcaInvoice, $result);

            // Step 6: Archive XML if successful
            if ($result['success'] && $result['status'] === 'CLEARED') {
                $this->archiveInvoiceXml($zatcaInvoice, $config);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('ZATCA Clearance submission failed: ' . $e->getMessage(), [
                'zatca_invoice_id' => $zatcaInvoice->id,
                'config_id' => $config->id,
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'error' => 'Clearance submission error: ' . $e->getMessage(),
                'status' => 'SUBMISSION_ERROR',
            ];
        }
    }

    /**
     * Prepare payload for ZATCA Clearance API
     */
    protected function prepareClearancePayload(ZatcaInvoice $zatcaInvoice, ZatcaConfiguration $config): array
    {
        // Get the signed XML content
        $signedXml = $zatcaInvoice->xml_content;

        // Prepare headers
        $headers = $this->getClearanceHeaders($config);

        // Prepare payload according to ZATCA specifications
        $payload = [
            'invoice' => base64_encode($signedXml),
            'uuid' => $zatcaInvoice->invoice_uuid,
            'invoiceHash' => $zatcaInvoice->invoice_hash,
            'previousInvoiceHash' => $zatcaInvoice->previous_hash,
            'clearanceStatus' => 'CLEARED', // For B2B invoices
        ];

        return [
            'payload' => $payload,
            'headers' => $headers,
        ];
    }

    /**
     * Submit to ZATCA Clearance API
     */
    protected function submitToClearanceApi(array $payloadData, ZatcaConfiguration $config): array
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
            Log::error('ZATCA Clearance API request failed: ' . $e->getMessage());
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
     * Process ZATCA Clearance API response
     */
    protected function processClearanceResponse(array $response, ZatcaInvoice $zatcaInvoice, ZatcaConfiguration $config): array
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
        if ($response['status_code'] === 200 && isset($responseData['clearanceStatus'])) {
            $clearanceStatus = $responseData['clearanceStatus'];
            
            return [
                'success' => true,
                'status' => $clearanceStatus,
                'clearance_response' => $responseData,
                'clearance_id' => $responseData['clearanceId'] ?? null,
                'clearance_timestamp' => $responseData['clearanceTimestamp'] ?? now()->toISOString(),
                'message' => 'Invoice successfully processed',
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
     * Get headers for ZATCA Clearance API
     */
    protected function getClearanceHeaders(ZatcaConfiguration $config): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->getAccessToken($config),
            'Invoice-Type' => 'B2B',
            'Clearance-Status' => 'CLEARED',
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
            'scope' => 'invoice_clearance',
        ];

        // In production, make actual token request to ZATCA OAuth endpoint
        // $response = Http::post($tokenEndpoint, $tokenData);
        
        // For demonstration, return a mock token
        $mockToken = 'zatca_mock_token_' . time();
        
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
     * Update invoice status based on clearance result
     */
    protected function updateInvoiceStatus(ZatcaInvoice $zatcaInvoice, array $result): void
    {
        $zatcaInvoice->zatca_status = $result['status'];
        $zatcaInvoice->zatca_response = json_encode($result);
        $zatcaInvoice->clearance_timestamp = $result['clearance_timestamp'] ?? now();
        $zatcaInvoice->save();

        Log::info('ZATCA invoice status updated', [
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
            $archivePath = storage_path("zatca/archived/{$zatcaInvoice->company_id}");
            
            if (!file_exists($archivePath)) {
                mkdir($archivePath, 0755, true);
            }

            $filename = "invoice_{$zatcaInvoice->invoice_uuid}_cleared_" . date('Y-m-d_H-i-s') . '.xml';
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
     * Get clearance status for an invoice
     */
    public function getClearanceStatus(ZatcaInvoice $zatcaInvoice, ZatcaConfiguration $config): array
    {
        try {
            $endpoint = $this->getEndpoint($config) . '/' . $zatcaInvoice->invoice_uuid;
            $headers = $this->getClearanceHeaders($config);

            $response = Http::withHeaders($headers)->get($endpoint);

            if ($response->successful()) {
                $statusData = $response->json();
                
                return [
                    'success' => true,
                    'status' => $statusData['clearanceStatus'] ?? 'UNKNOWN',
                    'data' => $statusData,
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get clearance status',
                'status_code' => $response->status(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error getting clearance status: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Batch submit multiple invoices for clearance
     */
    public function batchSubmitForClearance(array $zatcaInvoiceIds, ZatcaConfiguration $config): array
    {
        $results = [];
        $zatcaInvoices = ZatcaInvoice::whereIn('id', $zatcaInvoiceIds)->get();

        foreach ($zatcaInvoices as $zatcaInvoice) {
            $result = $this->submitInvoiceForClearance($zatcaInvoice, $config);
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
}
