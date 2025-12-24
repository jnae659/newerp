<?php

namespace App\Services;

use App\Models\ZatcaConfiguration;
use App\Models\ZatcaInvoice;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ZatcaInvoiceService
{
    protected $zatcaTaxService;

    public function __construct(ZatcaTaxService $zatcaTaxService)
    {
        $this->zatcaTaxService = $zatcaTaxService;
    }

    /**
     * Prepare ZATCA invoice data from regular invoice
     */
    public function prepareZatcaInvoiceData(Invoice $invoice, ZatcaConfiguration $config)
    {
        // Generate unique UUID for ZATCA
        $uuid = $this->generateUUID();
        
        // Generate ZATCA invoice number
        $zatcaInvoiceNumber = $this->generateZatcaInvoiceNumber($invoice, $config);
        
        // Determine invoice type based on amount and configuration
        $invoiceType = $this->determineInvoiceType($invoice, $config);
        
        // Extract customer data
        $customerData = $this->extractCustomerData($invoice);
        
        // Extract invoice line items
        $lineItems = $this->extractLineItems($invoice);
        
        // Calculate totals and VAT
        $totals = $this->calculateInvoiceTotals($invoice, $lineItems);
        
        $zatcaData = [
            'uuid' => $uuid,
            'invoice_number' => $zatcaInvoiceNumber,
            'invoice_type' => $invoiceType,
            'invoice_date' => $invoice->issue_date,
            'due_date' => $invoice->due_date,
            'seller' => [
                'tax_number' => $config->zatca_tax_number,
                'branch_code' => $config->zatca_branch_code,
                'name' => $invoice->company->name,
                'address' => [
                    'city' => $invoice->company->address ?? '',
                    'country' => 'SA', // Saudi Arabia
                    'postal_code' => $invoice->company->zip_code ?? '',
                ],
            ],
            'buyer' => $customerData,
            'line_items' => $lineItems,
            'totals' => $totals,
            'payment_method' => $invoice->payment_method ?? 'CASH',
            'currency' => $invoice->currency ?? 'SAR',
            'created_at' => now()->toISOString(),
        ];

        return $zatcaData;
    }

    /**
     * Generate unique UUID for ZATCA invoice
     */
    protected function generateUUID()
    {
        return strtoupper(Str::uuid()->toString());
    }

    /**
     * Generate ZATCA compliant invoice number
     */
    protected function generateZatcaInvoiceNumber(Invoice $invoice, ZatcaConfiguration $config)
    {
        $branchCode = $config->zatca_branch_code ?? '000';
        $deviceId = $config->zatca_device_id ?? '000000';
        $invoiceDate = date('Ymd', strtotime($invoice->issue_date));
        $sequence = str_pad($invoice->id, 9, '0', STR_PAD_LEFT);
        
        return "{$branchCode}-{$deviceId}-{$invoiceDate}-{$sequence}";
    }

    /**
     * Determine invoice type based on amount and configuration
     */
    protected function determineInvoiceType(Invoice $invoice, ZatcaConfiguration $config)
    {
        $amount = $invoice->total ?? 0;
        
        // Simplified invoice for Phase 2 (B2C, amount <= 1000 SAR)
        if ($config->zatca_phase === 'phase2' && $amount <= 1000) {
            return 'simplified';
        }
        
        // Standard invoice for Phase 1 or higher amounts
        return 'standard';
    }

    /**
     * Extract customer data for ZATCA
     */
    protected function extractCustomerData(Invoice $invoice)
    {
        $customer = $invoice->customer;
        
        if (!$customer) {
            return [
                'name' => 'Unknown Customer',
                'tax_number' => null,
                'address' => [
                    'city' => '',
                    'country' => 'SA',
                ],
            ];
        }

        return [
            'name' => $customer->name,
            'tax_number' => $customer->tax_number ?? null,
            'address' => [
                'city' => $customer->billing_city ?? '',
                'country' => $customer->billing_country ?? 'SA',
                'postal_code' => $customer->billing_zip_code ?? '',
                'street' => $customer->billing_address ?? '',
            ],
        ];
    }

    /**
     * Extract line items from invoice
     */
    protected function extractLineItems(Invoice $invoice)
    {
        $lineItems = [];
        $invoiceProducts = InvoiceProduct::where('invoice_id', $invoice->id)->get();
        
        foreach ($invoiceProducts as $index => $product) {
            $quantity = $product->quantity ?? 1;
            $unitPrice = $product->price ?? 0;
            $totalPrice = $product->price * $quantity;
            
            // Calculate VAT
            $vatCalculation = $this->zatcaTaxService->calculateVAT($totalPrice);
            
            $lineItem = [
                'line_number' => $index + 1,
                'description' => $product->product->name ?? $product->product_name,
                'quantity' => $quantity,
                'unit_price' => $this->zatcaTaxService->formatAmount($unitPrice),
                'net_amount' => $this->zatcaTaxService->formatAmount($totalPrice),
                'vat_rate' => $vatCalculation['vat_rate'],
                'vat_amount' => $vatCalculation['vat_amount'],
                'total_amount' => $vatCalculation['total_amount'],
                'vat_category_code' => $this->getVATCategoryCode($vatCalculation['vat_rate']),
            ];
            
            $lineItems[] = $lineItem;
        }
        
        return $lineItems;
    }

    /**
     * Calculate invoice totals
     */
    protected function calculateInvoiceTotals(Invoice $invoice, array $lineItems)
    {
        $totalNet = 0;
        $totalVat = 0;
        $totalGross = 0;
        
        foreach ($lineItems as $lineItem) {
            $totalNet += $lineItem['net_amount'];
            $totalVat += $lineItem['vat_amount'];
            $totalGross += $lineItem['total_amount'];
        }
        
        return [
            'total_net' => $this->zatcaTaxService->formatAmount($totalNet),
            'total_vat' => $this->zatcaTaxService->formatAmount($totalVat),
            'total_gross' => $this->zatcaTaxService->formatAmount($totalGross),
            'discount_amount' => 0, // Could be calculated from invoice discounts
        ];
    }

    /**
     * Get VAT category code based on rate
     */
    protected function getVATCategoryCode($vatRate)
    {
        $vatRates = $this->zatcaTaxService->getSaudiVatRates();
        
        if ($vatRate == $vatRates['standard']) {
            return 'S'; // Standard rate
        } elseif ($vatRate == $vatRates['zero_rated']) {
            return 'Z'; // Zero rate
        } else {
            return 'E'; // Exempt
        }
    }

    /**
     * Generate QR code for Phase 2 invoices
     */
    public function generateQRCode(array $zatcaData)
    {
        try {
            // QR code data structure for ZATCA Phase 2
            $qrData = [
                'seller_name' => $zatcaData['seller']['name'],
                'seller_tax_number' => $zatcaData['seller']['tax_number'],
                'invoice_date' => $zatcaData['invoice_date'],
                'invoice_total' => $zatcaData['totals']['total_gross'],
                'vat_total' => $zatcaData['totals']['total_vat'],
                'uuid' => $zatcaData['uuid'],
            ];
            
            // Convert to JSON and encode
            $qrString = json_encode($qrData, JSON_UNESCAPED_UNICODE);
            
            // Generate QR code (using a simple implementation)
            // In production, you might want to use a proper QR library
            return base64_encode($qrString);
            
        } catch (\Exception $e) {
            throw new \Exception('Error generating QR code: ' . $e->getMessage());
        }
    }

    /**
     * Generate digital signature for Phase 2 invoices
     */
    public function generateDigitalSignature(array $zatcaData, ZatcaConfiguration $config)
    {
        try {
            if (empty($config->zatca_certificate_path) || empty($config->zatca_private_key_path)) {
                throw new \Exception('Certificate and private key are required for digital signature');
            }
            
            // Prepare data for signing
            $dataToSign = json_encode([
                'uuid' => $zatcaData['uuid'],
                'invoice_number' => $zatcaData['invoice_number'],
                'total_amount' => $zatcaData['totals']['total_gross'],
                'vat_amount' => $zatcaData['totals']['total_vat'],
            ]);
            
            // In a real implementation, you would use openssl_sign() with the private key
            // For this example, we'll create a simple hash signature
            $privateKeyPath = storage_path('app/' . $config->zatca_private_key_path);
            
            if (!file_exists($privateKeyPath)) {
                throw new \Exception('Private key file not found');
            }
            
            $privateKey = file_get_contents($privateKeyPath);
            $signature = '';
            
            // This is a simplified signature generation
            // In production, use proper cryptographic signing
            openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            
            return base64_encode($signature);
            
        } catch (\Exception $e) {
            throw new \Exception('Error generating digital signature: ' . $e->getMessage());
        }
    }

    /**
     * Validate ZATCA invoice data
     */
    public function validateZatcaInvoiceData(array $zatcaData)
    {
        $errors = [];
        
        // Required fields validation
        $requiredFields = ['uuid', 'invoice_number', 'invoice_type', 'seller', 'buyer', 'line_items', 'totals'];
        
        foreach ($requiredFields as $field) {
            if (!isset($zatcaData[$field])) {
                $errors[] = "Required field missing: {$field}";
            }
        }
        
        // Seller validation
        if (isset($zatcaData['seller'])) {
            if (empty($zatcaData['seller']['tax_number'])) {
                $errors[] = 'Seller tax number is required';
            }
        }
        
        // Line items validation
        if (isset($zatcaData['line_items']) && is_array($zatcaData['line_items'])) {
            if (empty($zatcaData['line_items'])) {
                $errors[] = 'At least one line item is required';
            }
        }
        
        // Totals validation
        if (isset($zatcaData['totals'])) {
            if ($zatcaData['totals']['total_gross'] < 0) {
                $errors[] = 'Total gross amount cannot be negative';
            }
        }
        
        return $errors;
    }

    /**
     * Format ZATCA invoice for display
     */
    public function formatForDisplay(array $zatcaData)
    {
        return [
            'uuid' => $zatcaData['uuid'],
            'invoice_number' => $zatcaData['invoice_number'],
            'invoice_type' => ucfirst($zatcaData['invoice_type']),
            'invoice_date' => $zatcaData['invoice_date'],
            'seller' => [
                'name' => $zatcaData['seller']['name'],
                'tax_number' => $zatcaData['seller']['tax_number'],
                'branch_code' => $zatcaData['seller']['branch_code'] ?? '',
            ],
            'buyer' => [
                'name' => $zatcaData['buyer']['name'],
                'tax_number' => $zatcaData['buyer']['tax_number'] ?? 'N/A',
            ],
            'totals' => $zatcaData['totals'],
            'line_items_count' => count($zatcaData['line_items']),
            'currency' => $zatcaData['currency'] ?? 'SAR',
        ];
    }
}
