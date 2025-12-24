<?php

namespace App\Services;

use App\Models\ZatcaConfiguration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZatcaApiService
{
    /**
     * Submit invoice to ZATCA API
     */
    public function submitInvoice(array $invoiceData, ZatcaConfiguration $config)
    {
        try {
            $endpoint = $this->getApiEndpoint($config);
            
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-API-KEY' => $config->zatca_api_key,
                ])
                ->post($endpoint, [
                    'uuid' => $invoiceData['uuid'],
                    'invoice_number' => $invoiceData['invoice_number'],
                    'invoice_type' => $invoiceData['invoice_type'],
                    'data' => $invoiceData,
                    'signature' => $invoiceData['digital_signature'] ?? null,
                ]);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'status' => $result['status'] ?? 'valid',
                    'data' => $result,
                    'message' => $result['message'] ?? 'Invoice submitted successfully',
                ];
            } else {
                Log::error('ZATCA API Error: ' . $response->body());
                return [
                    'status' => 'invalid',
                    'error' => 'API Error: ' . $response->status(),
                    'message' => $response->body(),
                ];
            }

        } catch (\Exception $e) {
            Log::error('ZATCA API Exception: ' . $e->getMessage());
            return [
                'status' => 'invalid',
                'error' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Submit simplified invoice (Phase 2)
     */
    public function submitSimplifiedInvoice(array $invoiceData, ZatcaConfiguration $config)
    {
        try {
            $endpoint = $this->getSimplifiedApiEndpoint($config);
            
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-API-KEY' => $config->zatca_api_key,
                ])
                ->post($endpoint, [
                    'uuid' => $invoiceData['uuid'],
                    'invoice_number' => $invoiceData['invoice_number'],
                    'data' => $invoiceData,
                    'qr_code' => $invoiceData['qr_code'] ?? null,
                ]);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'status' => $result['status'] ?? 'valid',
                    'data' => $result,
                    'message' => $result['message'] ?? 'Simplified invoice submitted successfully',
                ];
            } else {
                Log::error('ZATCA Simplified API Error: ' . $response->body());
                return [
                    'status' => 'invalid',
                    'error' => 'API Error: ' . $response->status(),
                    'message' => $response->body(),
                ];
            }

        } catch (\Exception $e) {
            Log::error('ZATCA Simplified API Exception: ' . $e->getMessage());
            return [
                'status' => 'invalid',
                'error' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test ZATCA API connection
     */
    public function testConnection(ZatcaConfiguration $config)
    {
        try {
            $endpoint = $this->getApiEndpoint($config) . '/health';
            
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-API-KEY' => $config->zatca_api_key,
                ])
                ->get($endpoint);

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'response' => $response->json(),
            ];

        } catch (\Exception $e) {
            Log::error('ZATCA Connection Test Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get ZATCA API endpoint based on phase and configuration
     */
    protected function getApiEndpoint(ZatcaConfiguration $config)
    {
        // For production, this would be the actual ZATCA endpoints
        if ($config->zatca_phase === 'phase1') {
            return $config->zatca_api_endpoint . '/v1/invoices';
        } elseif ($config->zatca_phase === 'phase2') {
            return $config->zatca_api_endpoint . '/v2/invoices/standard';
        }
        
        throw new \Exception('Invalid ZATCA phase configuration');
    }

    /**
     * Get simplified invoice API endpoint
     */
    protected function getSimplifiedApiEndpoint(ZatcaConfiguration $config)
    {
        return $config->zatca_api_endpoint . '/v2/invoices/simplified';
    }

    /**
     * Get ZATCA production endpoints
     */
    public function getProductionEndpoints()
    {
        return [
            'phase1' => 'https://api.zatca.gov.sa/v1',
            'phase2_standard' => 'https://api.zatca.gov.sa/v2/invoices/standard',
            'phase2_simplified' => 'https://api.zatca.gov.sa/v2/invoices/simplified',
            'sandbox_standard' => 'https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal',
        ];
    }

    /**
     * Validate API credentials
     */
    public function validateCredentials(ZatcaConfiguration $config)
    {
        $errors = [];

        if (empty($config->zatca_api_key)) {
            $errors[] = 'API Key is required';
        }

        if (empty($config->zatca_api_secret)) {
            $errors[] = 'API Secret is required';
        }

        if (empty($config->zatca_api_endpoint)) {
            $errors[] = 'API Endpoint is required';
        }

        if ($config->zatca_phase === 'phase2') {
            if (empty($config->zatca_tax_number)) {
                $errors[] = 'Tax Number is required for Phase 2';
            }

            if (empty($config->zatca_branch_code)) {
                $errors[] = 'Branch Code is required for Phase 2';
            }
        }

        return $errors;
    }
}
