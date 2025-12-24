<?php

namespace App\Services;

use App\Models\ZatcaConfiguration;
use App\Models\ZatcaInvoice;
use Illuminate\Support\Facades\Log;

class ZatcaQrCodeService
{
    protected $zatcaHashService;
    protected $zatcaSignatureService;

    public function __construct(ZatcaHashService $zatcaHashService, ZatcaSignatureService $zatcaSignatureService)
    {
        $this->zatcaHashService = $zatcaHashService;
        $this->zatcaSignatureService = $zatcaSignatureService;
    }

    /**
     * Generate TLV encoded QR code for ZATCA Phase 2
     * Implements ZATCA's specific TLV encoding requirements
     */
    public function generateQrCode(string $xmlContent, ZatcaConfiguration $config, ZatcaInvoice $zatcaInvoice): array
    {
        try {
            // Extract required data from XML
            $qrData = $this->extractQrData($xmlContent, $config, $zatcaInvoice);
            
            // Generate TLV encoded string
            $tlvString = $this->generateTlvString($qrData);
            
            // Generate QR code image
            $qrCodeImage = $this->generateQrCodeImage($tlvString);
            
            // Calculate QR code hash
            $qrHash = $this->calculateQrHash($tlvString);
            
            return [
                'success' => true,
                'tlv_string' => $tlvString,
                'qr_code_image' => $qrCodeImage,
                'qr_hash' => $qrHash,
                'qr_data' => $qrData,
            ];
            
        } catch (\Exception $e) {
            Log::error('ZATCA QR Code generation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Extract data required for QR code from XML and configuration
     */
    protected function extractQrData(string $xmlContent, ZatcaConfiguration $config, ZatcaInvoice $zatcaInvoice): array
    {
        try {
            $xmlDoc = new \DOMDocument();
            $xmlDoc->loadXML($xmlContent);
            
            // Extract seller information
            $sellerName = $this->extractTextContent($xmlDoc, 'RegistrationName', 'AccountingSupplierParty');
            $sellerTaxNumber = $config->zatca_tax_number;
            
            // Extract invoice data
            $invoiceDate = $this->extractTextContent($xmlDoc, 'IssueDate');
            $invoiceTime = $this->extractTextContent($xmlDoc, 'IssueTime');
            $invoiceTotal = $this->extractTextContent($xmlDoc, 'TaxInclusiveAmount', 'LegalMonetaryTotal');
            $vatAmount = $this->extractTextContent($xmlDoc, 'TaxAmount', 'TaxTotal');
            
            // Extract invoice hash
            $invoiceHash = $zatcaInvoice->invoice_hash;
            
            // Extract digital signature (for Phase 2)
            $signature = null;
            if ($config->zatca_phase === 'phase2') {
                $signatureResult = $this->zatcaSignatureService->verifySignature($xmlContent, $config);
                if ($signatureResult['is_valid']) {
                    $signature = $signatureResult['signature_data'] ?? null;
                }
            }
            
            return [
                'seller_name' => $sellerName,
                'seller_tax_number' => $sellerTaxNumber,
                'invoice_date' => $invoiceDate,
                'invoice_time' => $invoiceTime,
                'invoice_total' => $invoiceTotal,
                'vat_amount' => $vatAmount,
                'invoice_hash' => $invoiceHash,
                'signature' => $signature,
                'timestamp' => now()->toISOString(),
            ];
            
        } catch (\Exception $e) {
            throw new \Exception('Failed to extract QR data: ' . $e->getMessage());
        }
    }

    /**
     * Generate TLV encoded string according to ZATCA specifications
     */
    protected function generateTlvString(array $qrData): string
    {
        $tlvParts = [];
        
        // Tag 1: Seller Name (String)
        $sellerName = $this->sanitizeText($qrData['seller_name']);
        $tlvParts[] = $this->encodeTlv('1', $sellerName);
        
        // Tag 2: VAT Registration Number (String)
        $taxNumber = $this->sanitizeText($qrData['seller_tax_number']);
        $tlvParts[] = $this->encodeTlv('2', $taxNumber);
        
        // Tag 3: Invoice Date (YYYY-MM-DD)
        $date = $this->sanitizeText($qrData['invoice_date']);
        $tlvParts[] = $this->encodeTlv('3', $date);
        
        // Tag 4: Invoice Total (Decimal)
        $total = number_format(floatval($qrData['invoice_total']), 2, '.', '');
        $tlvParts[] = $this->encodeTlv('4', $total);
        
        // Tag 5: VAT Amount (Decimal)
        $vat = number_format(floatval($qrData['vat_amount']), 2, '.', '');
        $tlvParts[] = $this->encodeTlv('5', $vat);
        
        // Tag 6: Invoice Hash (Hex String)
        $hash = strtoupper($qrData['invoice_hash']);
        $tlvParts[] = $this->encodeTlv('6', $hash);
        
        // Tag 7: Digital Signature (Base64) - Only for Phase 2
        if (!empty($qrData['signature'])) {
            $signature = $qrData['signature'];
            $tlvParts[] = $this->encodeTlv('7', $signature);
        }
        
        // Tag 8: Invoice Time (HH:MM:SS)
        $time = $this->sanitizeText($qrData['invoice_time']);
        $tlvParts[] = $this->encodeTlv('8', $time);
        
        return implode('', $tlvParts);
    }

    /**
     * Encode a single TLV (Tag-Length-Value) field
     */
    protected function encodeTlv(string $tag, string $value): string
    {
        // Convert tag to bytes (1 byte for tags 1-255)
        $tagByte = chr(intval($tag));
        
        // Calculate length (max 255 for simplicity, can be extended)
        $length = strlen($value);
        if ($length > 255) {
            throw new \Exception('TLV value too long');
        }
        $lengthByte = chr($length);
        
        // Convert value to bytes (assuming UTF-8)
        $valueBytes = $value;
        
        return $tagByte . $lengthByte . $valueBytes;
    }

    /**
     * Generate QR code image from TLV string
     */
    protected function generateQrCodeImage(string $tlvString): string
    {
        try {
            // For now, return base64 encoded placeholder
            // In production, you would use a QR code library like endroid/qr-code
            
            // Example QR code generation logic:
            // $qrCode = new QrCode($tlvString);
            // $qrCode->setSize(300);
            // $qrCode->setMargin(10);
            // $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH);
            
            // For demonstration, create a simple placeholder
            $qrImageData = $this->createPlaceholderQrCode($tlvString);
            return base64_encode($qrImageData);
            
        } catch (\Exception $e) {
            throw new \Exception('QR code image generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Create a placeholder QR code image
     * In production, replace with actual QR code generation
     */
    protected function createPlaceholderQrCode(string $data): string
    {
        // This is a placeholder implementation
        // In production, use a proper QR code library
        
        // Create a simple image with the data encoded
        $width = 300;
        $height = 300;
        $image = imagecreate($width, $height);
        
        // Set colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        // Fill background
        imagefill($image, 0, 0, $white);
        
        // Draw a simple pattern (placeholder)
        for ($i = 0; $i < 20; $i++) {
            for ($j = 0; $j < 20; $j++) {
                if (($i + $j) % 2 == 0) {
                    $x = $i * 15;
                    $y = $j * 15;
                    imagefilledrectangle($image, $x, $y, $x + 14, $y + 14, $black);
                }
            }
        }
        
        // Add text
        $text = 'ZATCA QR';
        $font = 5;
        $textWidth = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);
        $x = ($width - $textWidth) / 2;
        $y = $height - $textHeight - 10;
        imagestring($image, $font, $x, $y, $text, $black);
        
        // Capture output
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        
        imagedestroy($image);
        
        return $imageData;
    }

    /**
     * Calculate hash of QR code TLV string
     */
    protected function calculateQrHash(string $tlvString): string
    {
        return strtoupper(hash('sha256', $tlvString, false));
    }

    /**
     * Validate QR code TLV structure
     */
    public function validateTlvString(string $tlvString): array
    {
        $errors = [];
        $parsedData = [];
        
        try {
            $offset = 0;
            $length = strlen($tlvString);
            
            while ($offset < $length) {
                if ($offset + 2 > $length) {
                    $errors[] = 'Incomplete TLV at offset ' . $offset;
                    break;
                }
                
                // Read tag
                $tag = ord($tlvString[$offset]);
                $offset++;
                
                // Read length
                $valueLength = ord($tlvString[$offset]);
                $offset++;
                
                // Check if we have enough data
                if ($offset + $valueLength > $length) {
                    $errors[] = 'TLV value truncated for tag ' . $tag;
                    break;
                }
                
                // Read value
                $value = substr($tlvString, $offset, $valueLength);
                $offset += $valueLength;
                
                // Parse based on tag
                $parsedData[$tag] = $this->parseTlvValue($tag, $value);
            }
            
            // Validate required fields
            $this->validateRequiredFields($parsedData, $errors);
            
        } catch (\Exception $e) {
            $errors[] = 'TLV parsing error: ' . $e->getMessage();
        }
        
        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'parsed_data' => $parsedData,
        ];
    }

    /**
     * Parse TLV value based on tag
     */
    protected function parseTlvValue(int $tag, string $value)
    {
        switch ($tag) {
            case 1: // Seller Name
            case 2: // Tax Number
            case 3: // Date
            case 4: // Total (as string)
            case 5: // VAT Amount (as string)
            case 6: // Hash
            case 7: // Signature
            case 8: // Time
                return $value;
            default:
                return $value; // Unknown tag, return as-is
        }
    }

    /**
     * Validate required fields in parsed TLV data
     */
    protected function validateRequiredFields(array $data, array &$errors): void
    {
        $requiredTags = [1, 2, 3, 4, 5, 6, 8]; // Tags 1,2,3,4,5,6,8 are required
        
        foreach ($requiredTags as $tag) {
            if (!isset($data[$tag])) {
                $errors[] = 'Missing required TLV tag: ' . $tag;
            }
        }
        
        // Validate tag 6 (hash format)
        if (isset($data[6])) {
            $hash = $data[6];
            if (!$this->isValidHashFormat($hash)) {
                $errors[] = 'Invalid hash format in tag 6';
            }
        }
    }

    /**
     * Extract text content from XML by tag name and optional parent
     */
    protected function extractTextContent(\DOMDocument $xmlDoc, string $tagName, ?string $parentTag = null): string
    {
        $elements = $xmlDoc->getElementsByTagName($tagName);
        
        if ($elements->length === 0) {
            return '';
        }
        
        // If parent specified, find element within parent
        if ($parentTag) {
            foreach ($elements as $element) {
                $parent = $element->parentNode;
                while ($parent && $parent->nodeName !== $parentTag) {
                    $parent = $parent->parentNode;
                }
                if ($parent && $parent->nodeName === $parentTag) {
                    return trim($element->textContent);
                }
            }
        }
        
        // Return first matching element
        return trim($elements->item(0)->textContent);
    }

    /**
     * Sanitize text for TLV encoding
     */
    protected function sanitizeText(string $text): string
    {
        // Remove control characters except tab, newline, carriage return
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E\x{80}-\x{10FFFF}]/u', '', $text);
        return trim($text);
    }

    /**
     * Check if hash format is valid
     */
    protected function isValidHashFormat(string $hash): bool
    {
        return preg_match('/^[A-F0-9]{64}$/', strtoupper($hash)) === 1;
    }

    /**
     * Get QR code as data URL
     */
    public function getQrCodeDataUrl(string $qrCodeImage): string
    {
        return 'data:image/png;base64,' . $qrCodeImage;
    }

    /**
     * Generate QR code for invoice and store
     */
    public function generateAndStoreQrCode(ZatcaInvoice $zatcaInvoice, ZatcaConfiguration $config): array
    {
        try {
            // Generate QR code
            $qrResult = $this->generateQrCode($zatcaInvoice->xml_content, $config, $zatcaInvoice);
            
            if (!$qrResult['success']) {
                return $qrResult;
            }
            
            // Update zatca invoice with QR code data
            $zatcaInvoice->qr_code_tlv = $qrResult['tlv_string'];
            $zatcaInvoice->qr_code_image = $qrResult['qr_code_image'];
            $zatcaInvoice->qr_code_hash = $qrResult['qr_hash'];
            $zatcaInvoice->save();
            
            return [
                'success' => true,
                'qr_code_url' => $this->getQrCodeDataUrl($qrResult['qr_code_image']),
                'qr_data' => $qrResult['qr_data'],
            ];
            
        } catch (\Exception $e) {
            Log::error('ZATCA QR code generation and storage failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
