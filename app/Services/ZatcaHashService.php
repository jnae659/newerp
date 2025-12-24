<?php

namespace App\Services;

use App\Models\ZatcaInvoice;
use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ZatcaHashService
{
    /**
     * Calculate cryptographic hash from invoice data
     * This implements ZATCA's required hash algorithm
     */
    public function calculateInvoiceHash(array $invoiceData): string
    {
        // Sort the data to ensure consistent ordering
        ksort($invoiceData);
        
        // Create canonical string representation
        $canonicalString = $this->createCanonicalString($invoiceData);
        
        // Apply ZATCA-specific hashing algorithm
        // Using SHA-256 as specified by ZATCA Phase 2
        $hash = hash('sha256', $canonicalString, false);
        
        return strtoupper($hash);
    }

    /**
     * Get the hash of a previous invoice for chaining
     */
    public function getInvoiceHash(ZatcaInvoice $previousInvoice): ?string
    {
        if (!$previousInvoice->invoice_hash) {
            return null;
        }
        
        return $previousInvoice->invoice_hash;
    }

    /**
     * Get the previous invoice for hash chaining
     */
    public function getPreviousInvoice(Invoice $currentInvoice, int $companyId): ?ZatcaInvoice
    {
        return ZatcaInvoice::where('invoice_id', $currentInvoice->id)
            ->where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Create canonical string from invoice data
     * This ensures consistent hash calculation
     */
    protected function createCanonicalString(array $data): string
    {
        $canonicalParts = [];
        
        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '') {
                $canonicalParts[] = $key . '=' . $value;
            }
        }
        
        return implode('&', $canonicalParts);
    }

    /**
     * Calculate hash from XML content (for Phase 2)
     */
    public function calculateXmlHash(string $xmlContent): string
    {
        // Canonicalize the XML first
        $canonicalizedXml = $this->canonicalizeXml($xmlContent);
        
        // Calculate hash of canonicalized XML
        return strtoupper(hash('sha256', $canonicalizedXml, false));
    }

    /**
     * Canonicalize XML for hash calculation
     * This removes insignificant whitespace and normalizes the XML
     */
    protected function canonicalizeXml(string $xml): string
    {
        // Remove XML declaration
        $xml = preg_replace('/<\?xml[^?]*\?>/', '', $xml);
        
        // Remove comments
        $xml = preg_replace('/<!--.*?-->/s', '', $xml);
        
        // Normalize whitespace
        $xml = preg_replace('/\s+/', ' ', $xml);
        
        // Remove spaces around tags
        $xml = preg_replace('/>\s+</', '><', $xml);
        
        // Trim
        $xml = trim($xml);
        
        return $xml;
    }

    /**
     * Validate invoice hash chaining sequence
     */
    public function validateHashChain(array $invoices): bool
    {
        $previousHash = null;
        
        foreach ($invoices as $invoice) {
            $currentHash = $this->calculateInvoiceHash($invoice);
            
            // For first invoice, previous hash should be empty
            if ($previousHash === null) {
                if ($invoice['previous_hash']) {
                    return false; // First invoice should not have previous hash
                }
            } else {
                // For subsequent invoices, previous hash should match
                if ($invoice['previous_hash'] !== $previousHash) {
                    return false; // Hash chaining broken
                }
            }
            
            $previousHash = $currentHash;
        }
        
        return true;
    }

    /**
     * Generate invoice hash for database storage
     */
    public function generateInvoiceHash(Invoice $invoice, ZatcaConfiguration $config, ?ZatcaInvoice $previousInvoice = null): array
    {
        $invoiceData = $this->prepareInvoiceDataForHash($invoice, $config);
        
        // Calculate current hash
        $currentHash = $this->calculateInvoiceHash($invoiceData);
        
        // Get previous hash if exists
        $previousHash = $previousInvoice ? $previousInvoice->invoice_hash : null;
        
        return [
            'current_hash' => $currentHash,
            'previous_hash' => $previousHash,
            'invoice_data' => $invoiceData,
        ];
    }

    /**
     * Prepare invoice data specifically for hash calculation
     */
    protected function prepareInvoiceDataForHash(Invoice $invoice, ZatcaConfiguration $config): array
    {
        $invoiceProducts = \App\Models\InvoiceProduct::where('invoice_id', $invoice->id)->get();
        
        $lineItems = [];
        foreach ($invoiceProducts as $product) {
            $lineItems[] = [
                'id' => $product->id,
                'product_id' => $product->product_id,
                'quantity' => $product->quantity,
                'price' => number_format($product->price, 2, '.', ''),
                'total' => number_format($product->price * $product->quantity, 2, '.', ''),
            ];
        }
        
        // Sort line items by ID for consistency
        usort($lineItems, function($a, $b) {
            return $a['id'] <=> $b['id'];
        });
        
        return [
            'invoice_id' => $invoice->invoice_id,
            'issue_date' => $invoice->issue_date,
            'issue_time' => $invoice->issue_time ?? '00:00:00',
            'customer_id' => $invoice->customer_id,
            'sub_total' => number_format($invoice->sub_total ?? 0, 2, '.', ''),
            'total_tax' => number_format($invoice->total_tax ?? 0, 2, '.', ''),
            'total_amount' => number_format($invoice->total_amount ?? 0, 2, '.', ''),
            'zatca_tax_number' => $config->zatca_tax_number,
            'zatca_branch_code' => $config->zatca_branch_code ?? '',
            'line_items' => $lineItems,
            'currency' => 'SAR',
        ];
    }

    /**
     * Verify hash integrity of stored invoice
     */
    public function verifyInvoiceHash(ZatcaInvoice $zatcaInvoice, Invoice $invoice, ZatcaConfiguration $config): bool
    {
        $invoiceData = $this->prepareInvoiceDataForHash($invoice, $config);
        $calculatedHash = $this->calculateInvoiceHash($invoiceData);
        
        return strtoupper($zatcaInvoice->invoice_hash) === strtoupper($calculatedHash);
    }

    /**
     * Store hash audit trail
     */
    public function storeHashAudit(string $invoiceUuid, string $hash, string $previousHash, array $invoiceData): void
    {
        $auditData = [
            'invoice_uuid' => $invoiceUuid,
            'current_hash' => $hash,
            'previous_hash' => $previousHash,
            'invoice_data' => json_encode($invoiceData),
            'calculated_at' => now()->toISOString(),
        ];
        
        // Store in audit storage for compliance
        Storage::put("zatca/audit/{$invoiceUuid}_hash.json", json_encode($auditData, JSON_PRETTY_PRINT));
    }

    /**
     * Generate chain hash for sequential validation
     */
    public function generateChainHash(array $hashes): string
    {
        $chainString = implode('|', $hashes);
        return strtoupper(hash('sha256', $chainString, false));
    }

    /**
     * Validate complete hash chain for a company
     */
    public function validateCompanyHashChain(int $companyId): array
    {
        $invoices = ZatcaInvoice::where('company_id', $companyId)
            ->orderBy('created_at')
            ->get();
        
        $validationResult = [
            'is_valid' => true,
            'errors' => [],
            'chain_details' => [],
        ];
        
        $previousHash = null;
        $sequence = 1;
        
        foreach ($invoices as $zatcaInvoice) {
            $currentHash = $zatcaInvoice->invoice_hash;
            
            // Check if previous hash matches
            if ($zatcaInvoice->previous_hash !== $previousHash) {
                $validationResult['is_valid'] = false;
                $validationResult['errors'][] = "Invoice sequence {$sequence}: Previous hash mismatch";
            }
            
            // Check hash format
            if (!$this->isValidHashFormat($currentHash)) {
                $validationResult['is_valid'] = false;
                $validationResult['errors'][] = "Invoice sequence {$sequence}: Invalid hash format";
            }
            
            $validationResult['chain_details'][] = [
                'sequence' => $sequence,
                'invoice_uuid' => $zatcaInvoice->invoice_uuid,
                'current_hash' => $currentHash,
                'previous_hash' => $zatcaInvoice->previous_hash,
                'is_valid' => $zatcaInvoice->previous_hash === $previousHash,
            ];
            
            $previousHash = $currentHash;
            $sequence++;
        }
        
        return $validationResult;
    }

    /**
     * Check if hash format is valid
     */
    protected function isValidHashFormat(string $hash): bool
    {
        // ZATCA requires 64-character hexadecimal hash
        return preg_match('/^[A-F0-9]{64}$/', strtoupper($hash)) === 1;
    }

    /**
     * Calculate cumulative hash for reporting period
     */
    public function calculatePeriodHash(array $invoices): string
    {
        $hashes = [];
        foreach ($invoices as $invoice) {
            $hashes[] = $invoice->invoice_hash;
        }
        
        return $this->generateChainHash($hashes);
    }
}
