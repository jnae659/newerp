<?php

namespace App\Services;

use App\Models\ZatcaConfiguration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class ZatcaSignatureService
{
    protected $zatcaHashService;
    protected $zatcaValidationService;

    public function __construct(ZatcaHashService $zatcaHashService, ZatcaValidationService $zatcaValidationService)
    {
        $this->zatcaHashService = $zatcaHashService;
        $this->zatcaValidationService = $zatcaValidationService;
    }

    /**
     * Generate ECDSA signature for XML document
     * This implements ZATCA's required digital signature algorithm
     */
    public function generateSignature(string $xmlContent, ZatcaConfiguration $config): array
    {
        try {
            // Load the private key from configuration
            $privateKey = $this->loadPrivateKey($config);
            
            if (!$privateKey) {
                throw new \Exception('Private key not found or invalid');
            }

            // Canonicalize XML for signing
            $canonicalizedXml = $this->canonicalizeForSigning($xmlContent);
            
            // Calculate hash of canonicalized XML
            $xmlHash = $this->zatcaHashService->calculateXmlHash($canonicalizedXml);
            
            // Create signature using ECDSA with SHA-256
            $signature = $this->signData($xmlHash, $privateKey);
            
            if (!$signature) {
                throw new \Exception('Failed to generate signature');
            }

            // Encode signature in base64
            $base64Signature = base64_encode($signature);
            
            // Generate signature XML fragment
            $signatureXml = $this->buildSignatureXml($base64Signature, $config);
            
            return [
                'success' => true,
                'signature' => $base64Signature,
                'signature_xml' => $signatureXml,
                'xml_hash' => $xmlHash,
                'signed_xml' => $this->insertSignatureIntoXml($xmlContent, $signatureXml),
            ];
            
        } catch (\Exception $e) {
            Log::error('ZATCA Signature generation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify digital signature
     */
    public function verifySignature(string $xmlContent, ZatcaConfiguration $config): array
    {
        try {
            // Extract signature from XML
            $signature = $this->extractSignatureFromXml($xmlContent);
            
            if (!$signature) {
                return [
                    'is_valid' => false,
                    'error' => 'Signature not found in XML',
                ];
            }

            // Load public key for verification
            $publicKey = $this->loadPublicKey($config);
            
            if (!$publicKey) {
                return [
                    'is_valid' => false,
                    'error' => 'Public key not found or invalid',
                ];
            }

            // Canonicalize XML (excluding signature) for verification
            $xmlWithoutSignature = $this->removeSignatureFromXml($xmlContent);
            $canonicalizedXml = $this->canonicalizeForSigning($xmlWithoutSignature);
            
            // Calculate hash of canonicalized XML
            $xmlHash = $this->zatcaHashService->calculateXmlHash($canonicalizedXml);
            
            // Verify signature
            $isValid = $this->verifyData($xmlHash, base64_decode($signature), $publicKey);
            
            return [
                'is_valid' => $isValid,
                'xml_hash' => $xmlHash,
                'signature_data' => $signature,
            ];
            
        } catch (\Exception $e) {
            Log::error('ZATCA Signature verification failed: ' . $e->getMessage());
            return [
                'is_valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate CSR (Certificate Signing Request)
     */
    public function generateCsr(ZatcaConfiguration $config): array
    {
        try {
            $distinguishedName = [
                'countryName' => 'SA',
                'stateOrProvinceName' => 'Riyadh',
                'localityName' => 'Riyadh',
                'organizationName' => $config->zatca_company_name ?? 'Company Name',
                'organizationalUnitName' => 'IT Department',
                'commonName' => $config->zatca_tax_number,
                'emailAddress' => $config->zatca_contact_email ?? 'admin@company.com',
            ];

            $privateKey = $this->generatePrivateKey();
            
            if (!$privateKey) {
                throw new \Exception('Failed to generate private key');
            }

            // Generate CSR
            $csr = openssl_csr_new($distinguishedName, $privateKey, [
                'digest_alg' => 'sha256',
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]);

            if (!$csr) {
                throw new \Exception('Failed to generate CSR');
            }

            // Export CSR
            $csrString = '';
            openssl_csr_export($csr, $csrString);
            
            // Export private key
            $privateKeyString = '';
            openssl_pkey_export($privateKey, $privateKeyString);

            // Store keys in secure location
            $this->storeKeys($config, $privateKeyString, $csrString);

            return [
                'success' => true,
                'csr' => $csrString,
                'private_key' => $privateKeyString,
            ];
            
        } catch (\Exception $e) {
            Log::error('ZATCA CSR generation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Import ZATCA certificate
     */
    public function importCertificate(string $certificateData, ZatcaConfiguration $config): bool
    {
        try {
            // Validate certificate format
            if (!openssl_x509_read($certificateData)) {
                throw new \Exception('Invalid certificate format');
            }

            // Store certificate securely
            $certificatePath = $this->getCertificatePath($config);
            file_put_contents($certificatePath, $certificateData);

            // Update configuration
            $config->zatca_certificate_path = $certificatePath;
            $config->save();

            return true;
            
        } catch (\Exception $e) {
            Log::error('ZATCA Certificate import failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Sign data using ECDSA with SHA-256
     */
    protected function signData(string $data, $privateKey): ?string
    {
        $signature = '';
        $result = openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        
        if (!$result) {
            return null;
        }
        
        return $signature;
    }

    /**
     * Verify data using ECDSA with SHA-256
     */
    protected function verifyData(string $data, string $signature, $publicKey): bool
    {
        return openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    /**
     * Canonicalize XML for signing
     * This applies ZATCA's specific canonicalization rules
     */
    protected function canonicalizeForSigning(string $xml): string
    {
        // Remove XML declaration
        $xml = preg_replace('/<\?xml[^?]*\?>/', '', $xml);
        
        // Remove signature extension if present
        $xml = preg_replace('/<ext:UBLExtension>.*?<\/ext:UBLExtension>/s', '', $xml);
        
        // Normalize line endings
        $xml = str_replace(["\r\n", "\r"], "\n", $xml);
        
        // Remove comments
        $xml = preg_replace('/<!--.*?-->/s', '', $xml);
        
        // Normalize whitespace in text content
        $xml = preg_replace('/\s+/', ' ', $xml);
        
        // Remove spaces around tags
        $xml = preg_replace('/>\s+</', '><', $xml);
        
        // Trim
        $xml = trim($xml);
        
        return $xml;
    }

    /**
     * Build signature XML fragment
     */
    protected function buildSignatureXml(string $signature, ZatcaConfiguration $config): string
    {
        $certificatePath = $this->getCertificatePath($config);
        
        if (!file_exists($certificatePath)) {
            throw new \Exception('Certificate not found');
        }

        $certificateContent = file_get_contents($certificatePath);
        $base64Certificate = base64_encode($certificateContent);

        $signatureXml = '<ext:ExtensionContent>';
        $signatureXml .= '<sig:UBLDocumentSignatures>';
        $signatureXml .= '<sig:SignedObject>';
        $signatureXml .= '<ds:Signature>';
        $signatureXml .= '<ds:SignedInfo>';
        $signatureXml .= '<ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#">';
        $signatureXml .= '</ds:CanonicalizationMethod>';
        $signatureXml .= '<ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha256">';
        $signatureXml .= '</ds:SignatureMethod>';
        $signatureXml .= '<ds:Reference URI="">';
        $signatureXml .= '<ds:Transforms>';
        $signatureXml .= '<ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#">';
        $signatureXml .= '</ds:Transform>';
        $signatureXml .= '</ds:Transforms>';
        $signatureXml .= '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256">';
        $signatureXml .= '</ds:DigestMethod>';
        $signatureXml .= '<ds:DigestValue></ds:DigestValue>';
        $signatureXml .= '</ds:Reference>';
        $signatureXml .= '</ds:SignedInfo>';
        $signatureXml .= '<ds:SignatureValue>' . $signature . '</ds:SignatureValue>';
        $signatureXml .= '<ds:KeyInfo>';
        $signatureXml .= '<ds:X509Data>';
        $signatureXml .= '<ds:X509Certificate>' . $base64Certificate . '</ds:X509Certificate>';
        $signatureXml .= '</ds:X509Data>';
        $signatureXml .= '</ds:KeyInfo>';
        $signatureXml .= '</ds:Signature>';
        $signatureXml .= '</sig:SignedObject>';
        $signatureXml .= '</sig:UBLDocumentSignatures>';
        $signatureXml .= '</ext:ExtensionContent>';

        return $signatureXml;
    }

    /**
     * Insert signature into XML
     */
    protected function insertSignatureIntoXml(string $xmlContent, string $signatureXml): string
    {
        // Find the signature extension content and replace it
        $pattern = '/<ext:ExtensionContent>.*?<\/ext:ExtensionContent>/s';
        $replacement = $signatureXml;
        
        return preg_replace($pattern, $replacement, $xmlContent);
    }

    /**
     * Extract signature from XML
     */
    protected function extractSignatureFromXml(string $xmlContent): ?string
    {
        preg_match('/<ds:SignatureValue>(.*?)<\/ds:SignatureValue>/s', $xmlContent, $matches);
        
        return $matches[1] ?? null;
    }

    /**
     * Remove signature from XML for verification
     */
    protected function removeSignatureFromXml(string $xmlContent): string
    {
        // Remove the entire signature extension
        return preg_replace('/<ext:UBLExtension>.*?<\/ext:UBLExtension>/s', '', $xmlContent);
    }

    /**
     * Generate RSA private key
     */
    protected function generatePrivateKey()
    {
        $config = [
            "digest_alg" => "sha512",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        return openssl_pkey_new($config);
    }

    /**
     * Load private key from configuration
     */
    protected function loadPrivateKey(ZatcaConfiguration $config)
    {
        $privateKeyPath = $this->getPrivateKeyPath($config);
        
        if (!file_exists($privateKeyPath)) {
            return null;
        }

        $privateKeyData = file_get_contents($privateKeyPath);
        return openssl_pkey_get_private($privateKeyData);
    }

    /**
     * Load public key for verification
     */
    protected function loadPublicKey(ZatcaConfiguration $config)
    {
        $certificatePath = $this->getCertificatePath($config);
        
        if (!file_exists($certificatePath)) {
            return null;
        }

        $certificate = openssl_x509_read(file_get_contents($certificatePath));
        return openssl_pkey_get_public($certificate);
    }

    /**
     * Store keys securely
     */
    protected function storeKeys(ZatcaConfiguration $config, string $privateKey, string $csr): void
    {
        $privateKeyPath = $this->getPrivateKeyPath($config);
        $csrPath = $this->getCsrPath($config);
        
        // Ensure directory exists
        $directory = dirname($privateKeyPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0700, true);
        }
        
        // Store private key with restricted permissions
        file_put_contents($privateKeyPath, $privateKey);
        chmod($privateKeyPath, 0600);
        
        // Store CSR
        file_put_contents($csrPath, $csr);
    }

    /**
     * Get private key path
     */
    protected function getPrivateKeyPath(ZatcaConfiguration $config): string
    {
        return storage_path("zatca/private/{$config->id}_private_key.pem");
    }

    /**
     * Get CSR path
     */
    protected function getCsrPath(ZatcaConfiguration $config): string
    {
        return storage_path("zatca/csr/{$config->id}_csr.pem");
    }

    /**
     * Get certificate path
     */
    protected function getCertificatePath(ZatcaConfiguration $config): string
    {
        return storage_path("zatca/certificates/{$config->id}_certificate.pem");
    }

    /**
     * Validate certificate chain
     */
    public function validateCertificateChain(ZatcaConfiguration $config): array
    {
        try {
            $certificatePath = $this->getCertificatePath($config);
            
            if (!file_exists($certificatePath)) {
                return [
                    'is_valid' => false,
                    'error' => 'Certificate not found',
                ];
            }

            $certificate = openssl_x509_read(file_get_contents($certificatePath));
            
            if (!$certificate) {
                return [
                    'is_valid' => false,
                    'error' => 'Invalid certificate format',
                ];
            }

            // Get certificate information
            $certInfo = openssl_x509_parse($certificate);
            
            // Check if certificate is valid
            $isValid = ($certInfo['validFrom_time_t'] <= time()) && ($certInfo['validTo_time_t'] >= time());
            
            return [
                'is_valid' => $isValid,
                'certificate_info' => $certInfo,
                'valid_from' => date('Y-m-d H:i:s', $certInfo['validFrom_time_t']),
                'valid_to' => date('Y-m-d H:i:s', $certInfo['validTo_time_t']),
            ];
            
        } catch (\Exception $e) {
            return [
                'is_valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
