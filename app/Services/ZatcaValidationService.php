<?php

namespace App\Services;

use App\Models\ZatcaConfiguration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class ZatcaValidationService
{
    protected $zatcaHashService;
    protected $zatcaSignatureService;

    // ZATCA XSD Schema paths
    protected $xsdSchemas = [
        'invoice' => 'zatca/schemas/UBL-Invoice-2.1.xsd',
        'credit_note' => 'zatca/schemas/UBL-CreditNote-2.1.xsd',
        'debit_note' => 'zatca/schemas/UBL-DebitNote-2.1.xsd',
    ];

    public function __construct(ZatcaHashService $zatcaHashService, ZatcaSignatureService $zatcaSignatureService)
    {
        $this->zatcaHashService = $zatcaHashService;
        $this->zatcaSignatureService = $zatcaSignatureService;
    }

    /**
     * Validate XML against ZATCA XSD schema
     */
    public function validateXmlSchema(string $xmlContent, string $documentType = 'invoice'): array
    {
        try {
            // Load XML document
            $xmlDoc = new \DOMDocument();
            $xmlDoc->loadXML($xmlContent);
            
            // Get XSD schema path
            $xsdPath = $this->getXsdSchemaPath($documentType);
            
            if (!file_exists($xsdPath)) {
                return [
                    'is_valid' => false,
                    'error' => "XSD schema not found: {$xsdPath}",
                    'errors' => ['XSD schema file missing'],
                ];
            }

            // Enable user error handling
            libxml_use_internal_errors(true);
            libxml_clear_errors();

            // Validate XML against XSD
            $isValid = $xmlDoc->schemaValidate($xsdPath);

            if (!$isValid) {
                $errors = libxml_get_errors();
                $errorMessages = [];

                foreach ($errors as $error) {
                    $errorMessages[] = trim($error->message);
                }

                libxml_clear_errors();

                return [
                    'is_valid' => false,
                    'error' => 'XML validation failed against ZATCA XSD schema',
                    'errors' => $errorMessages,
                    'libxml_errors' => $errors,
                ];
            }

            return [
                'is_valid' => true,
                'message' => 'XML successfully validated against ZATCA XSD schema',
            ];

        } catch (\Exception $e) {
            Log::error('ZATCA XML validation failed: ' . $e->getMessage());
            return [
                'is_valid' => false,
                'error' => 'XML validation error: ' . $e->getMessage(),
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Validate ZATCA-specific business rules
     */
    public function validateBusinessRules(string $xmlContent, ZatcaConfiguration $config): array
    {
        $errors = [];
        $warnings = [];

        try {
            $xmlDoc = new \DOMDocument();
            $xmlDoc->loadXML($xmlContent);

            // Validate UBL version
            $ublVersion = $xmlDoc->getElementsByTagName('UBLVersionID')->item(0);
            if (!$ublVersion || $ublVersion->textContent !== 'UBL 2.1') {
                $errors[] = 'Invalid UBL version. Must be 2.1';
            }

            // Validate customization ID
            $customizationId = $xmlDoc->getElementsByTagName('CustomizationID')->item(0);
            if (!$customizationId) {
                $errors[] = 'Missing CustomizationID';
            } else {
                $customization = $customizationId->textContent;
                if (!str_contains($customization, 'urn:fdc:saudi:2022:vat:UBL:extension:v1.0')) {
                    $errors[] = 'Invalid CustomizationID for Saudi ZATCA';
                }
            }

            // Validate profile execution ID
            $profileExecutionId = $xmlDoc->getElementsByTagName('ProfileExecutionID')->item(0);
            if (!$profileExecutionId) {
                $errors[] = 'Missing ProfileExecutionID';
            } else {
                $executionId = $profileExecutionId->textContent;
                if ($config->zatca_phase === 'phase2' && $executionId !== '2.0') {
                    $errors[] = 'ProfileExecutionID must be 2.0 for Phase 2';
                } elseif ($config->zatca_phase === 'phase1' && $executionId !== '1.0') {
                    $errors[] = 'ProfileExecutionID must be 1.0 for Phase 1';
                }
            }

            // Validate tax currency
            $taxCurrency = $xmlDoc->getElementsByTagName('TaxCurrencyCode')->item(0);
            if (!$taxCurrency || $taxCurrency->textContent !== 'SAR') {
                $errors[] = 'TaxCurrencyCode must be SAR';
            }

            // Validate seller tax number
            $supplierParty = $xmlDoc->getElementsByTagName('AccountingSupplierParty')->item(0);
            if ($supplierParty) {
                $taxRegistrationId = $supplierParty->getElementsByTagName('TaxRegistrationID');
                if ($taxRegistrationId->length === 0) {
                    $errors[] = 'Missing seller TaxRegistrationID';
                } else {
                    $sellerTaxNumber = $taxRegistrationId->item(0)->textContent;
                    if ($sellerTaxNumber !== $config->zatca_tax_number) {
                        $errors[] = 'Seller TaxRegistrationID does not match configured ZATCA tax number';
                    }
                }
            }

            // Validate invoice lines
            $invoiceLines = $xmlDoc->getElementsByTagName('InvoiceLine');
            if ($invoiceLines->length === 0) {
                $errors[] = 'Invoice must have at least one line item';
            }

            // Validate totals consistency
            $this->validateTotalsConsistency($xmlDoc, $errors, $warnings);

            // Validate date/time formats
            $this->validateDateTimeFormats($xmlDoc, $errors);

            return [
                'is_valid' => empty($errors),
                'errors' => $errors,
                'warnings' => $warnings,
                'message' => empty($errors) ? 'Business rules validation passed' : 'Business rules validation failed',
            ];

        } catch (\Exception $e) {
            Log::error('ZATCA Business rules validation failed: ' . $e->getMessage());
            return [
                'is_valid' => false,
                'errors' => ['Business rules validation error: ' . $e->getMessage()],
                'warnings' => [],
            ];
        }
    }

    /**
     * Complete ZATCA compliance validation
     */
    public function validateZatcaCompliance(string $xmlContent, ZatcaConfiguration $config): array
    {
        $validationResults = [
            'overall_valid' => true,
            'schema_validation' => [],
            'business_rules' => [],
            'signature_validation' => [],
            'errors' => [],
            'warnings' => [],
        ];

        // 1. XSD Schema validation
        $schemaValidation = $this->validateXmlSchema($xmlContent);
        $validationResults['schema_validation'] = $schemaValidation;
        
        if (!$schemaValidation['is_valid']) {
            $validationResults['overall_valid'] = false;
            $validationResults['errors'] = array_merge($validationResults['errors'], $schemaValidation['errors']);
        }

        // 2. Business rules validation
        $businessRulesValidation = $this->validateBusinessRules($xmlContent, $config);
        $validationResults['business_rules'] = $businessRulesValidation;
        
        if (!$businessRulesValidation['is_valid']) {
            $validationResults['overall_valid'] = false;
            $validationResults['errors'] = array_merge($validationResults['errors'], $businessRulesValidation['errors']);
        }
        
        $validationResults['warnings'] = array_merge($validationResults['warnings'], $businessRulesValidation['warnings']);

        // 3. Digital signature validation (if Phase 2)
        if ($config->zatca_phase === 'phase2') {
            $signatureValidation = $this->zatcaSignatureService->verifySignature($xmlContent, $config);
            $validationResults['signature_validation'] = $signatureValidation;
            
            if (!$signatureValidation['is_valid']) {
                $validationResults['overall_valid'] = false;
                $validationResults['errors'][] = 'Digital signature validation failed: ' . ($signatureValidation['error'] ?? 'Unknown error');
            }
        }

        // 4. ZATCA-specific validations
        $zatcaValidations = $this->validateZatcaSpecificRequirements($xmlContent, $config);
        $validationResults['zatca_specific'] = $zatcaValidations;
        
        if (!$zatcaValidations['is_valid']) {
            $validationResults['overall_valid'] = false;
            $validationResults['errors'] = array_merge($validationResults['errors'], $zatcaValidations['errors']);
        }
        
        $validationResults['warnings'] = array_merge($validationResults['warnings'], $zatcaValidations['warnings']);

        $validationResults['message'] = $validationResults['overall_valid'] 
            ? 'ZATCA compliance validation passed' 
            : 'ZATCA compliance validation failed';

        return $validationResults;
    }

    /**
     * Validate ZATCA-specific requirements
     */
    protected function validateZatcaSpecificRequirements(string $xmlContent, ZatcaConfiguration $config): array
    {
        $errors = [];
        $warnings = [];

        try {
            $xmlDoc = new \DOMDocument();
            $xmlDoc->loadXML($xmlContent);

            // Check for ZATCA extensions
            $zatcaExtensions = $xmlDoc->getElementsByTagName('InvoiceHash');
            if ($zatcaExtensions->length === 0 && $config->zatca_phase === 'phase2') {
                $errors[] = 'Missing ZATCA InvoiceHash extension';
            }

            // Validate hash format (if present)
            if ($zatcaExtensions->length > 0) {
                $invoiceHash = $zatcaExtensions->item(0)->textContent;
                if (!$this->isValidHashFormat($invoiceHash)) {
                    $errors[] = 'Invalid invoice hash format. Must be 64-character hexadecimal';
                }
            }

            // Check for UUID
            $uuidElements = $xmlDoc->getElementsByTagName('UUID');
            if ($uuidElements->length === 0) {
                $errors[] = 'Missing UUID in invoice';
            } else {
                $uuid = $uuidElements->item(0)->textContent;
                if (!$this->isValidUuid($uuid)) {
                    $errors[] = 'Invalid UUID format';
                }
            }

            // Validate invoice type codes
            $invoiceTypeCode = $xmlDoc->getElementsByTagName('InvoiceTypeCode')->item(0);
            if (!$invoiceTypeCode) {
                $errors[] = 'Missing InvoiceTypeCode';
            } else {
                $typeCode = $invoiceTypeCode->textContent;
                $validTypeCodes = ['380', '388', '381', '383', '384', '385', '386', '387', '389', '395', '396', '388'];
                if (!in_array($typeCode, $validTypeCodes)) {
                    $warnings[] = "InvoiceTypeCode '{$typeCode}' may not be standard";
                }
            }

            // Validate line count
            $lineCount = $xmlDoc->getElementsByTagName('LineCountNumeric')->item(0);
            if (!$lineCount) {
                $warnings[] = 'Missing LineCountNumeric';
            }

            return [
                'is_valid' => empty($errors),
                'errors' => $errors,
                'warnings' => $warnings,
            ];

        } catch (\Exception $e) {
            return [
                'is_valid' => false,
                'errors' => ['ZATCA-specific validation error: ' . $e->getMessage()],
                'warnings' => [],
            ];
        }
    }

    /**
     * Validate totals consistency
     */
    protected function validateTotalsConsistency(\DOMDocument $xmlDoc, array &$errors, array &$warnings): void
    {
        try {
            // Get line items totals
            $lineItems = $xmlDoc->getElementsByTagName('InvoiceLine');
            $calculatedSubTotal = 0;
            $calculatedTaxTotal = 0;

            foreach ($lineItems as $lineItem) {
                $lineExtension = $lineItem->getElementsByTagName('LineExtensionAmount')->item(0);
                $taxAmount = $lineItem->getElementsByTagName('TaxAmount')->item(0);

                if ($lineExtension) {
                    $calculatedSubTotal += floatval($lineExtension->textContent);
                }

                if ($taxAmount) {
                    $calculatedTaxTotal += floatval($taxAmount->textContent);
                }
            }

            // Get document totals
            $legalTotal = $xmlDoc->getElementsByTagName('LegalMonetaryTotal')->item(0);
            if ($legalTotal) {
                $documentSubTotal = $legalTotal->getElementsByTagName('TaxExclusiveAmount')->item(0);
                $documentTaxTotal = $xmlDoc->getElementsByTagName('TaxTotal')->item(0);

                if ($documentSubTotal) {
                    $docSubTotal = floatval($documentSubTotal->textContent);
                    if (abs($calculatedSubTotal - $docSubTotal) > 0.01) {
                        $errors[] = "Subtotal mismatch: Line items total ({$calculatedSubTotal}) != Document total ({$docSubTotal})";
                    }
                }

                if ($documentTaxTotal) {
                    $docTaxTotal = floatval($documentTaxTotal->getElementsByTagName('TaxAmount')->item(0)->textContent);
                    if (abs($calculatedTaxTotal - $docTaxTotal) > 0.01) {
                        $errors[] = "Tax total mismatch: Line items tax ({$calculatedTaxTotal}) != Document tax ({$docTaxTotal})";
                    }
                }
            }

        } catch (\Exception $e) {
            $errors[] = 'Error validating totals consistency: ' . $e->getMessage();
        }
    }

    /**
     * Validate date/time formats
     */
    protected function validateDateTimeFormats(\DOMDocument $xmlDoc, array &$errors): void
    {
        // Validate IssueDate format (YYYY-MM-DD)
        $issueDate = $xmlDoc->getElementsByTagName('IssueDate')->item(0);
        if ($issueDate && !$this->isValidDate($issueDate->textContent, 'Y-m-d')) {
            $errors[] = 'Invalid IssueDate format. Must be YYYY-MM-DD';
        }

        // Validate IssueTime format (HH:MM:SS)
        $issueTime = $xmlDoc->getElementsByTagName('IssueTime')->item(0);
        if ($issueTime && !$this->isValidTime($issueTime->textContent)) {
            $errors[] = 'Invalid IssueTime format. Must be HH:MM:SS';
        }
    }

    /**
     * Get XSD schema path
     */
    protected function getXsdSchemaPath(string $documentType): string
    {
        return storage_path($this->xsdSchemas[$documentType] ?? $this->xsdSchemas['invoice']);
    }

    /**
     * Download ZATCA XSD schemas
     */
    public function downloadZatcaSchemas(): array
    {
        try {
            $schemas = [
                'UBL-Invoice-2.1.xsd' => 'https://zatca.gov.sa/ar/E-Invoicing/Introduction/Documents/UBL-Invoice-2.1.xsd',
                'UBL-CreditNote-2.1.xsd' => 'https://zatca.gov.sa/ar/E-Invoicing/Introduction/Documents/UBL-CreditNote-2.1.xsd',
                'UBL-DebitNote-2.1.xsd' => 'https://zatca.gov.sa/ar/E-Invoicing/Introduction/Documents/UBL-DebitNote-2.1.xsd',
            ];

            $downloadDir = storage_path('zatca/schemas');
            if (!file_exists($downloadDir)) {
                mkdir($downloadDir, 0755, true);
            }

            $results = [];
            foreach ($schemas as $filename => $url) {
                try {
                    $content = file_get_contents($url);
                    if ($content) {
                        $filepath = $downloadDir . '/' . $filename;
                        file_put_contents($filepath, $content);
                        $results[$filename] = 'success';
                    } else {
                        $results[$filename] = 'failed';
                    }
                } catch (\Exception $e) {
                    $results[$filename] = 'error: ' . $e->getMessage();
                }
            }

            return [
                'success' => true,
                'results' => $results,
                'message' => 'ZATCA schemas download completed',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate hash format
     */
    protected function isValidHashFormat(string $hash): bool
    {
        return preg_match('/^[A-F0-9]{64}$/', strtoupper($hash)) === 1;
    }

    /**
     * Validate UUID format
     */
    protected function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid) === 1;
    }

    /**
     * Validate date format
     */
    protected function isValidDate(string $date, string $format): bool
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Validate time format
     */
    protected function isValidTime(string $time): bool
    {
        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $time) === 1;
    }
}
