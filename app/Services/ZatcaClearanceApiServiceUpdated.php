<?php

namespace App\Services;

use App\Models\ZatcaConfiguration;
use App\Models\ZatcaInvoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ZatcaClearanceApiServiceUpdated
{
    protected $zatcaValidationService;
    protected $zatcaSignatureService;
    protected $zatcaAuthService;

    public function __construct(
        ZatcaValidationService $zatcaValidationService,
        ZatcaSignatureService $zatcaSignatureService,
        ZatcaAuthService $zatcaAuthService
    ) {
        $this->zatcaValidationService = $zatcaValidationService;
        $this->zatcaSignatureService = $zatcaSignatureService;
        $this->zatcaAuthService = $zatcaAuthService;
    }

    /**
     * Submit B2B invoice to ZATCA Clearance API using real authentication
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

            // Step 2: Check CSID validity
            if (!$this->zatcaAuthService->isCsidValid($config)) {
                return [
                    'success' => false,
                    'error' => 'CSID is invalid or expired. Please regenerate compliance CSID.',
                    'status' => 'CSID_INVALID',
                ];
            }

            // Step 3: Prepare request payload
            $payload = $this->prepareClearancePayload($zatcaInvoice, $config);

            // Step 4: Submit to real ZATCA Clearance API
            $response = $this->zatcaAuthService->makeApiCall('/clearance', $payload['payload'], $config);

            // Step 5: Process response
            $result = $this->processClearanceResponse($response, $zatcaInvoice, $config);

            // Step 6: Update invoice status
            $this->updateInvoiceStatus($zatcaInvoice, $result);

            // Step 7: Archive XML if successful
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
     * Prepare payload for ZATCA Clearance API using real format
     */
    protected function prepareClearcaInvoice $zatancePayload(ZatcaInvoice, ZatcaConfiguration $config): array
    {
        // Get the signed XML content
        $signedXml = $zatcaInvoice->xml_content;

        // Prepare payload according to real ZATCA specifications
        $payload = [
            'invoice' => base64_encode($signedXml),
            'uuid' => $zatcaInvoice->invoice_uuid,
            'invoiceHash' => $zatcaInvoice->invoice_hash,
            'previousInvoiceHash' => $zatcaInvoice->previous_hash,
            'clearanceStatus' => 'CLEARED', // For B2B invoices
        ];

        return [
            'payload' => $payload,
        ];
    }

    /**
     * Process ZATCA Clearance API response from real API
     */
    protected function processClearanceResponse(array $response, ZatcaInvoice $zatcaInvoice, ZatcaConfiguration $config): array
    {
        if (!$response['success']) {
            return [
                'success' => false,
                'error' => $response['error'] ?? 'API request failed',
                'status_code' => $response['status_code'] ?? 0,
                'response_data' => $response['response_data'] ?? null,
                'status' => 'API_ERROR',
            ];
        }

        $responseData = $response['data'];

        // Handle successful response from real ZATCA API
        if (isset($responseData['clearanceStatus'])) {
            $clearanceStatus = $responseData['clearanceStatus'];
            
            return [
                'success' => true,
                'status' => $clearanceStatus,
                'clearance_response' => $responseData,
                'clearance_id' => $responseData['clearanceId'] ?? null,
                'clearance_timestamp' => $responseData['clearanceTimestamp'] ?? now()->toISOString(),
                'message' => 'Invoice successfully processed by ZATCA',
            ];
        }

        // Handle error responses from real ZATCA API
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
     * Get clearance status for an invoice using real ZATCA API
     */
    public function getClearanceStatus(ZatcaInvoice $zatcaInvoice, ZatcaConfiguration $config): array
    {
        try {
            // Check CSID validity first
            if (!$this->zatcaAuthService->isCsidValid($config)) {
                return [
                    'success' => false,
                    'error' => 'CSID is invalid or expired',
                    'status' => 'CSID_INVALID',
                ];
            }

            // Use real ZATCA API for status check
            $endpoint = '/clearance/' . $zatcaInvoice->invoice_uuid;
            $response = $this->zatcaAuthService->makeApiCall($endpoint, [], $config);

            if ($response['success'] && isset($response['data']['clearanceStatus'])) {
                return [
                    'success' => true,
                    'status' => $response['data']['clearanceStatus'],
                    'data' => $response['data'],
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get clearance status',
                'status_code' => $response['status_code'] ?? 0,
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
            sleep(2);
        }

        return [
            'success' => true,
            'results' => $results,
            'total_processed' => count($results),
            'successful' => count(array_filter($results, function($r) { return $r['success']; })),
        ];
    }

    /**
     * Test clearance API connectivity with real ZATCA endpoint
     */
    public function testClearanceApi(ZatcaConfiguration $config): array
    {
        try {
            // Check CSID validity
            if (!$this->zatcaAuthService->isCsidValid($config)) {
                return [
                    'success' => false,
                    'error' => 'CSID is invalid or expired',
                    'csid_status' => $this->zatcaAuthService->getCsidStatus($config),
                ];
            }

            // Test API call with minimal payload
            $testPayload = [
                'invoice' => base64_encode('<?xml version="1.0"?><test/>'),
                'uuid' => 'test-uuid-' . time(),
                'invoiceHash' => hash('sha256', 'test'),
                'clearanceStatus' => 'TEST',
            ];

            $response = $this->zatcaAuthService->makeApiCall('/clearance', $testPayload, $config);

            return [
                'success' => $response['success'],
                'api_response' => $response,
                'csid_status' => $this->zatcaAuthService->getCsidStatus($config),
                'message' => $response['success'] ? 'Clearance API is reachable' : 'Clearance API test failed',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'API test exception: ' . $e->getMessage(),
                'csid_status' => $this->zatcaAuthService->getCsidStatus($config),
            ];
        }
    }
}
