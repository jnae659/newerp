<?php

namespace App\Services;

use App\Models\ZatcaConfiguration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ZatcaAuthService
{
    // ZATCA Sandbox API endpoints
    protected $baseUrl = 'https://gw-fatoora.zatca.gov.sa/e-invoice/developer-portal';
    protected $complianceEndpoint = '/compliance';
    protected $clearanceEndpoint = '/clearance';
    protected $reportingEndpoint = '/reporting';

    /**
     * Generate Compliance CSID (Cryptographic Stamp Identifier)
     * This is the first step to get authenticated with ZATCA sandbox
     */
    public function generateComplianceCsid(ZatcaConfiguration $config, string $otp, string $csr): array
    {
        try {
            Log::info('Starting ZATCA Compliance CSID generation', [
                'config_id' => $config->id,
                'company_id' => $config->company_id,
            ]);

            // Prepare headers for compliance API
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'OTP' => $otp,
                'Accept-Version' => 'V2',
            ];

            // Prepare request body
            $requestBody = [
                'csr' => $csr,
            ];

            // Make request to ZATCA compliance API
            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->post($this->baseUrl . $this->complianceEndpoint, $requestBody);

            // Handle response
            if ($response->successful()) {
                $responseData = $response->json();
                
                // Store CSID credentials
                $this->storeCsidCredentials($config, $responseData);
                
                Log::info('ZATCA Compliance CSID generated successfully', [
                    'config_id' => $config->id,
                    'request_id' => $responseData['requestID'] ?? null,
                    'disposition' => $responseData['dispositionMessage'] ?? null,
                ]);

                return [
                    'success' => true,
                    'request_id' => $responseData['requestID'] ?? null,
                    'disposition_message' => $responseData['dispositionMessage'] ?? null,
                    'binary_security_token' => $responseData['binarySecurityToken'] ?? null,
                    'secret' => $responseData['secret'] ?? null,
                    'message' => 'Compliance CSID generated successfully',
                ];
            }

            // Handle error responses
            $errorData = $response->json();
            $errorMessage = $this->extractErrorMessage($errorData, $response->status());

            Log::error('ZATCA Compliance CSID generation failed', [
                'config_id' => $config->id,
                'status_code' => $response->status(),
                'error_response' => $errorData,
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'status_code' => $response->status(),
                'response_data' => $errorData,
            ];

        } catch (\Exception $e) {
            Log::error('ZATCA Compliance CSID generation exception', [
                'config_id' => $config->id,
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Exception during CSID generation: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate real CSR for ZATCA compliance
     */
    public function generateComplianceCsr(ZatcaConfiguration $config): array
    {
        try {
            Log::info('Generating ZATCA Compliance CSR', [
                'config_id' => $config->id,
                'company_name' => $config->zatca_company_name ?? 'Unknown',
                'tax_number' => $config->zatca_tax_number,
            ]);

            // Prepare Distinguished Name for CSR
            $distinguishedName = [
                'countryName' => 'SA',
                'stateOrProvinceName' => 'Riyadh',
                'localityName' => 'Riyadh',
                'organizationName' => $config->zatca_company_name ?? 'Company Name',
                'organizationalUnitName' => 'IT Department',
                'commonName' => $config->zatca_tax_number,
                'emailAddress' => $config->zatca_contact_email ?? 'admin@company.com',
            ];

            // Generate private key
            $privateKeyConfig = [
                'digest_alg' => 'sha256',
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ];

            $privateKey = openssl_pkey_new($privateKeyConfig);
            
            if (!$privateKey) {
                throw new \Exception('Failed to generate private key');
            }

            // Generate CSR
            $csrConfig = [
                'digest_alg' => 'sha256',
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ];

            $csr = openssl_csr_new($distinguishedName, $privateKey, $csrConfig);
            
            if (!$csr) {
                throw new \Exception('Failed to generate CSR');
            }

            // Export CSR in PEM format
            $csrString = '';
            openssl_csr_export($csr, $csrString);

            // Export private key
            $privateKeyString = '';
            openssl_pkey_export($privateKey, $privateKeyString);

            // Store private key securely
            $this->storePrivateKey($config, $privateKeyString);

            Log::info('ZATCA Compliance CSR generated successfully', [
                'config_id' => $config->id,
                'csr_length' => strlen($csrString),
            ]);

            return [
                'success' => true,
                'csr' => $csrString,
                'private_key' => $privateKeyString,
                'distinguished_name' => $distinguishedName,
                'message' => 'CSR generated successfully',
            ];

        } catch (\Exception $e) {
            Log::error('ZATCA CSR generation failed', [
                'config_id' => $config->id,
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'CSR generation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check if CSID is still valid and not expired
     */
    public function isCsidValid(ZatcaConfiguration $config): bool
    {
        if (!$config->zatca_csid_binary_token || !$config->zatca_csid_secret) {
            return false;
        }

        // Check if CSID is not expired (assuming 1 year validity)
        if ($config->zatca_csid_issued_at) {
            $issuedAt = Carbon::parse($config->zatca_csid_issued_at);
            $expiryDate = $issuedAt->addYear();
            
            if (now()->isAfter($expiryDate)) {
                Log::warning('ZATCA CSID expired', [
                    'config_id' => $config->id,
                    'issued_at' => $config->zatca_csid_issued_at,
                    'expiry_date' => $expiryDate->toISOString(),
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Get valid authentication headers for API calls
     */
    public function getAuthHeaders(ZatcaConfiguration $config): array
    {
        if (!$this->isCsidValid($config)) {
            throw new \Exception('CSID is invalid or expired. Please regenerate.');
        }

        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Accept-Version' => 'V2',
            'Authorization' => 'Bearer ' . $config->zatca_csid_binary_token,
            'X-CSID-Secret' => $config->zatca_csid_secret,
        ];
    }

    /**
     * Make authenticated API call to ZATCA
     */
    public function makeApiCall(string $endpoint, array $payload, ZatcaConfiguration $config): array
    {
        try {
            $headers = $this->getAuthHeaders($config);
            $fullEndpoint = $this->baseUrl . $endpoint;

            Log::info('Making ZATCA API call', [
                'config_id' => $config->id,
                'endpoint' => $endpoint,
                'method' => 'POST',
            ]);

            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->post($fullEndpoint, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status_code' => $response->status(),
                    'data' => $response->json(),
                ];
            }

            // Handle error responses
            $errorData = $response->json();
            $errorMessage = $this->extractErrorMessage($errorData, $response->status());

            Log::error('ZATCA API call failed', [
                'config_id' => $config->id,
                'endpoint' => $endpoint,
                'status_code' => $response->status(),
                'error_response' => $errorData,
            ]);

            return [
                'success' => false,
                'status_code' => $response->status(),
                'error' => $errorMessage,
                'response_data' => $errorData,
            ];

        } catch (\Exception $e) {
            Log::error('ZATCA API call exception', [
                'config_id' => $config->id,
                'endpoint' => $endpoint,
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'API call exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Store CSID credentials in configuration
     */
    protected function storeCsidCredentials(ZatcaConfiguration $config, array $csidData): void
    {
        $config->zatca_csid_binary_token = $csidData['binarySecurityToken'] ?? null;
        $config->zatca_csid_secret = $csidData['secret'] ?? null;
        $config->zatca_csid_request_id = $csidData['requestID'] ?? null;
        $config->zatca_csid_disposition = $csidData['dispositionMessage'] ?? null;
        $config->zatca_csid_issued_at = now()->toISOString();
        $config->zatca_csid_status = $csidData['dispositionMessage'] === 'ISSUED' ? 'ACTIVE' : 'PENDING';
        $config->save();

        Log::info('ZATCA CSID credentials stored', [
            'config_id' => $config->id,
            'csid_status' => $config->zatca_csid_status,
            'request_id' => $config->zatca_csid_request_id,
        ]);
    }

    /**
     * Store private key securely
     */
    protected function storePrivateKey(ZatcaConfiguration $config, string $privateKey): void
    {
        $privateKeyPath = $this->getPrivateKeyPath($config);
        
        // Ensure directory exists
        $directory = dirname($privateKeyPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0700, true);
        }
        
        // Store private key with restricted permissions
        file_put_contents($privateKeyPath, $privateKey);
        chmod($privateKeyPath, 0600);
        
        // Update configuration with path
        $config->zatca_private_key_path = $privateKeyPath;
        $config->save();

        Log::info('ZATCA private key stored securely', [
            'config_id' => $config->id,
            'private_key_path' => $privateKeyPath,
        ]);
    }

    /**
     * Get private key path
     */
    protected function getPrivateKeyPath(ZatcaConfiguration $config): string
    {
        return storage_path("zatca/private/{$config->id}_private_key.pem");
    }

    /**
     * Extract error message from ZATCA response
     */
    protected function extractErrorMessage(array $responseData, int $statusCode): string
    {
        // Handle structured error responses
        if (isset($responseData['errors']) && is_array($responseData['errors'])) {
            $errorMessages = [];
            foreach ($responseData['errors'] as $error) {
                if (isset($error['message'])) {
                    $errorMessages[] = $error['message'];
                } elseif (isset($error['code'])) {
                    $errorMessages[] = $error['code'];
                }
            }
            
            if (!empty($errorMessages)) {
                return implode('; ', $errorMessages);
            }
        }

        // Handle simple error responses
        if (isset($responseData['message'])) {
            return $responseData['message'];
        }

        if (isset($responseData['code'])) {
            return $responseData['code'];
        }

        // Handle HTTP status codes
        switch ($statusCode) {
            case 400:
                return 'Bad Request - Invalid request parameters';
            case 401:
                return 'Unauthorized - Invalid or expired CSID';
            case 403:
                return 'Forbidden - Access denied';
            case 406:
                return 'Not Acceptable - Unsupported API version';
            case 500:
                return 'Internal Server Error - ZATCA service error';
            default:
                return "HTTP Error {$statusCode}";
        }
    }

    /**
     * Get CSID status for display
     */
    public function getCsidStatus(ZatcaConfiguration $config): array
    {
        $isValid = $this->isCsidValid($config);
        
        return [
            'is_valid' => $isValid,
            'status' => $config->zatca_csid_status ?? 'NOT_SET',
            'issued_at' => $config->zatca_csid_issued_at,
            'request_id' => $config->zatca_csid_request_id,
            'disposition' => $config->zatca_csid_disposition,
            'has_credentials' => !empty($config->zatca_csid_binary_token),
            'message' => $this->getStatusMessage($config, $isValid),
        ];
    }

    /**
     * Get status message for display
     */
    protected function getStatusMessage(ZatcaConfiguration $config, bool $isValid): string
    {
        if (!$config->zatca_csid_status) {
            return 'CSID not generated. Please generate compliance CSID first.';
        }

        if ($config->zatca_csid_status === 'PENDING') {
            return 'CSID generation pending. Please check your OTP and try again.';
        }

        if ($config->zatca_csid_status === 'ACTIVE' && !$isValid) {
            return 'CSID has expired. Please regenerate.';
        }

        if ($config->zatca_csid_status === 'ACTIVE' && $isValid) {
            return 'CSID is active and valid.';
        }

        return 'CSID status: ' . $config->zatca_csid_status;
    }
}
