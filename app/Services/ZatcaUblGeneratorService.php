<?php

namespace App\Services;

use App\Models\ZatcaConfiguration;
use App\Models\ZatcaInvoice;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Customer;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ZatcaUblGeneratorService
{
    protected $zatcaHashService;

    public function __construct(ZatcaHashService $zatcaHashService)
    {
        $this->zatcaHashService = $zatcaHashService;
    }

    /**
     * Generate UBL 2.1 XML for ZATCA Phase 2 compliance
     */
    public function generateUblXml(Invoice $invoice, ZatcaConfiguration $config, ?ZatcaInvoice $previousInvoice = null): string
    {
        // Generate UUID v4 for this invoice
        $uuid = strtoupper(Str::uuid()->toString());
        
        // Get previous invoice hash for chaining
        $previousHash = $previousInvoice ? $this->zatcaHashService->getInvoiceHash($previousInvoice) : null;
        
        // Calculate current invoice hash
        $invoiceData = $this->prepareInvoiceData($invoice, $config);
        $currentHash = $this->zatcaHashService->calculateInvoiceHash($invoiceData);
        
        // Generate UBL XML structure
        $ublXml = $this->buildUblStructure($invoice, $config, $uuid, $previousHash, $currentHash);
        
        return $ublXml;
    }

    /**
     * Build complete UBL 2.1 XML structure with ZATCA extensions
     */
    protected function buildUblStructure(Invoice $invoice, ZatcaConfiguration $config, string $uuid, ?string $previousHash, string $currentHash): string
    {
        $issueDate = Carbon::parse($invoice->issue_date)->toISOString();
        $issueTime = Carbon::parse($invoice->issue_date . ' ' . ($invoice->issue_time ?? '00:00:00'))->timezone('UTC')->format('H:i:s');
        
        $customer = $invoice->customer;
        $sellerTaxNumber = $config->zatca_tax_number;
        $buyerTaxNumber = $customer ? ($customer->tax_number ?? null) : null;
        
        // Determine invoice type for ZATCA
        $invoiceTypeCode = $this->determineInvoiceTypeCode($invoice, $buyerTaxNumber);
        
        $ubl = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $ubl .= '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" ';
        $ubl .= 'xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" ';
        $ubl .= 'xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" ';
        $ubl .= 'xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2" ';
        $ubl .= 'xmlns:sig="urn:oasis:names:specification:ubl:schema:xsd:CommonSignatureComponents-2" ';
        $ubl .= 'xmlns:sig-cac="urn:oasis:names:specification:ubl:schema:xsd:CommonSignatureAggregateComponents-2" ';
        $ubl .= 'xmlns:sig-cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonSignatureBasicComponents-2" ';
        $ubl .= 'xmlns:ds="http://www.w3.org/2000/09/xmldsig#" ';
        $ubl .= 'xmlns:zac="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2" ';
        $ubl .= 'xmlns:zaid="https://zatca.gov.sa/2022/v1/UBL-Extended" ';
        $ubl .= 'xmlns:ns18="urn:oasis:names:specification:ubl:schema:xsd:SignatureAggregateComponents-2" ';
        $ubl .= 'xmlns:ns17="urn:oasis:names:specification:ubl:schema:xsd:SignatureBasicComponents-2">';
        
        // UBLExtensions
        $ubl .= $this->buildUblExtensions($config, $uuid, $currentHash, $previousHash);
        
        // UBLVersionID
        $ubl .= '<cbc:UBLVersionID>UBL 2.1</cbc:UBLVersionID>';
        
        // CustomizationID
        $ubl .= '<cbc:CustomizationID>urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:01:1.0#urn:fdc:saudi:2022:vat:UBL:extension:v1.0</cbc:CustomizationID>';
        
        // ProfileID
        $ubl .= '<cbc:ProfileID>reporting:1.0</cbc:ProfileID>';
        
        // ProfileExecutionID
        $ubl .= '<cbc:ProfileExecutionID>'. ($config->zatca_phase === 'phase2' ? '2.0' : '1.0') .'</cbc:ProfileExecutionID>';
        
        // ID (Invoice Number)
        $ubl .= '<cbc:ID>' . htmlspecialchars($invoice->invoice_id) . '</cbc:ID>';
        
        // UUID
        $ubl .= '<cbc:UUID>' . $uuid . '</cbc:UUID>';
        
        // IssueDate
        $ubl .= '<cbc:IssueDate>' . date('Y-m-d', strtotime($invoice->issue_date)) . '</cbc:IssueDate>';
        
        // IssueTime (UTC)
        $ubl .= '<cbc:IssueTime>' . $issueTime . '</cbc:IssueTime>';
        
        // InvoiceTypeCode
        $ubl .= '<cbc:InvoiceTypeCode>' . $invoiceTypeCode . '</cbc:InvoiceTypeCode>';
        
        // Note (ZATCA extensions)
        $ubl .= '<cbc:Note languageID="ar">فاتورة ضريبية</cbc:Note>';
        
        // TaxCurrencyCode
        $ubl .= '<cbc:TaxCurrencyCode>SAR</cbc:TaxCurrencyCode>';
        
        // LineCountNumeric
        $lineCount = InvoiceProduct::where('invoice_id', $invoice->id)->count();
        $ubl .= '<cbc:LineCountNumeric>' . $lineCount . '</cbc:LineCountNumeric>';
        
        // AccountingSupplierParty
        $ubl .= $this->buildAccountingSupplierParty($invoice, $config, $sellerTaxNumber);
        
        // AccountingCustomerParty
        $ubl .= $this->buildAccountingCustomerParty($invoice, $customer, $buyerTaxNumber);
        
        // Delivery (optional)
        if ($invoice->due_date) {
            $ubl .= $this->buildDelivery($invoice);
        }
        
        // PaymentMeans
        $ubl .= $this->buildPaymentMeans($invoice);
        
        // TaxTotal
        $ubl .= $this->buildTaxTotal($invoice);
        
        // LegalMonetaryTotal
        $ubl .= $this->buildLegalMonetaryTotal($invoice);
        
        // InvoiceLine
        $ubl .= $this->buildInvoiceLines($invoice);
        
        $ubl .= '</Invoice>';
        
        return $ubl;
    }

    /**
     * Build UBLExtensions section with ZATCA-specific content
     */
    protected function buildUblExtensions(ZatcaConfiguration $config, string $uuid, string $currentHash, ?string $previousHash): string
    {
        $extensions = '<ext:UBLExtensions>';
        
        // ZATCA UBL Extension
        $extensions .= '<ext:UBLExtension>';
        $extensions .= '<ext:ExtensionURI>urn:oasis:names:specification:ubl:dsig:enveloped-signatures</ext:ExtensionURI>';
        $extensions .= '<ext:ExtensionContent>';
        // Signature will be added here later
        $extensions .= '</ext:ExtensionContent>';
        $extensions .= '</ext:UBLExtension>';
        
        // ZATCA Invoice Hash Extension
        $extensions .= '<ext:UBLExtension>';
        $extensions .= '<ext:ExtensionURI>https://zatca.gov.sa/2022/v1/UBL-Extended</ext:ExtensionURI>';
        $extensions .= '<ext:ExtensionContent>';
        $extensions .= '<zac:InvoiceHash>' . $currentHash . '</zac:InvoiceHash>';
        if ($previousHash) {
            $extensions .= '<zac:PreviousInvoiceHash>' . $previousHash . '</zac:PreviousInvoiceHash>';
        }
        $extensions .= '</ext:ExtensionContent>';
        $extensions .= '</ext:UBLExtension>';
        
        $extensions .= '</ext:UBLExtensions>';
        
        return $extensions;
    }

    /**
     * Build AccountingSupplierParty section
     */
    protected function buildAccountingSupplierParty(Invoice $invoice, ZatcaConfiguration $config, string $sellerTaxNumber): string
    {
        $party = '<cac:AccountingSupplierParty>';
        $party .= '<cac:Party>';
        
        // PartyName
        $party .= '<cac:PartyName>';
        $party .= '<cbc:Name>' . htmlspecialchars($invoice->company->name) . '</cbc:Name>';
        $party .= '</cac:PartyName>';
        
        // PostalAddress
        $party .= '<cac:PostalAddress>';
        $party .= '<cbc:StreetName>' . htmlspecialchars($invoice->company->address ?? '') . '</cbc:StreetName>';
        $party .= '<cbc:CityName>' . htmlspecialchars($invoice->company->city ?? '') . '</cbc:CityName>';
        $party .= '<cbc:PostalZone>' . htmlspecialchars($invoice->company->zip_code ?? '') . '</cbc:PostalZone>';
        $party .= '<cac:Country>';
        $party .= '<cbc:IdentificationCode>SA</cbc:IdentificationCode>';
        $party .= '</cac:Country>';
        $party .= '</cac:PostalAddress>';
        
        // PartyTaxScheme
        $party .= '<cac:PartyTaxScheme>';
        $party .= '<cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>';
        $party .= '<cac:TaxScheme>';
        $party .= '<cbc:ID>VAT</cbc:ID>';
        $party .= '</cac:TaxScheme>';
        $party .= '</cac:PartyTaxScheme>';
        
        // PartyLegalEntity
        $party .= '<cac:PartyLegalEntity>';
        $party .= '<cbc:RegistrationName>' . htmlspecialchars($invoice->company->name) . '</cbc:RegistrationName>';
        $party .= '<cbc:CompanyID>300000000000003</cbc:CompanyID>'; // Placeholder
        $party .= '</cac:PartyLegalEntity>';
        
        $party .= '</cac:Party>';
        $party .= '</cac:AccountingSupplierParty>';
        
        return $party;
    }

    /**
     * Build AccountingCustomerParty section
     */
    protected function buildAccountingCustomerParty(Invoice $invoice, ?Customer $customer, ?string $buyerTaxNumber): string
    {
        $party = '<cac:AccountingCustomerParty>';
        $party .= '<cac:Party>';
        
        // PartyName (if no tax number)
        if (!$buyerTaxNumber) {
            $party .= '<cac:PartyName>';
            $party .= '<cbc:Name>' . htmlspecialchars($customer ? $customer->name : 'Unknown Customer') . '</cbc:Name>';
            $party .= '</cac:PartyName>';
        }
        
        // PostalAddress
        if ($customer) {
            $party .= '<cac:PostalAddress>';
            $party .= '<cbc:StreetName>' . htmlspecialchars($customer->billing_address ?? '') . '</cbc:StreetName>';
            $party .= '<cbc:CityName>' . htmlspecialchars($customer->billing_city ?? '') . '</cbc:CityName>';
            $party .= '<cbc:PostalZone>' . htmlspecialchars($customer->billing_zip_code ?? '') . '</cbc:PostalZone>';
            $party .= '<cac:Country>';
            $party .= '<cbc:IdentificationCode>' . ($customer->billing_country ?? 'SA') . '</cbc:IdentificationCode>';
            $party .= '</cac:Country>';
            $party .= '</cac:PostalAddress>';
        }
        
        // PartyTaxScheme (only if tax number exists)
        if ($buyerTaxNumber) {
            $party .= '<cac:PartyTaxScheme>';
            $party .= '<cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>';
            $party .= '<cbc:TaxRegistrationID>' . $buyerTaxNumber . '</cbc:TaxRegistrationID>';
            $party .= '<cac:TaxScheme>';
            $party .= '<cbc:ID>VAT</cbc:ID>';
            $party .= '</cac:TaxScheme>';
            $party .= '</cac:PartyTaxScheme>';
        }
        
        // PartyLegalEntity
        $party .= '<cac:PartyLegalEntity>';
        $party .= '<cbc:RegistrationName>' . htmlspecialchars($customer ? $customer->name : 'Unknown Customer') . '</cbc:RegistrationName>';
        if ($buyerTaxNumber) {
            $party .= '<cbc:CompanyID>' . $buyerTaxNumber . '</cbc:CompanyID>';
        }
        $party .= '</cac:PartyLegalEntity>';
        
        $party .= '</cac:Party>';
        $party .= '</cac:AccountingCustomerParty>';
        
        return $party;
    }

    /**
     * Build Delivery section
     */
    protected function buildDelivery(Invoice $invoice): string
    {
        $delivery = '<cac:Delivery>';
        $delivery .= '<cbc:ActualDeliveryDate>' . date('Y-m-d', strtotime($invoice->due_date)) . '</cbc:ActualDeliveryDate>';
        $delivery .= '</cac:Delivery>';
        
        return $delivery;
    }

    /**
     * Build PaymentMeans section
     */
    protected function buildPaymentMeans(Invoice $invoice): string
    {
        $payment = '<cac:PaymentMeans>';
        $payment .= '<cbc:PaymentMeansCode>' . ($this->getPaymentMethodCode($invoice->payment_method ?? 'CASH')) . '</cbc:PaymentMeansCode>';
        $payment .= '</cac:PaymentMeans>';
        
        return $payment;
    }

    /**
     * Build TaxTotal section
     */
    protected function buildTaxTotal(Invoice $invoice): string
    {
        $invoiceProducts = InvoiceProduct::where('invoice_id', $invoice->id)->get();
        $totalVat = 0;
        
        foreach ($invoiceProducts as $product) {
            $totalVat += ($product->price * $product->quantity) * 0.15; // 15% VAT
        }
        
        $taxTotal = '<cac:TaxTotal>';
        $taxTotal .= '<cbc:TaxAmount currencyID="SAR">' . number_format($totalVat, 2, '.', '') . '</cbc:TaxAmount>';
        $taxTotal .= '<cac:TaxSubtotal>';
        $taxTotal .= '<cbc:TaxableAmount currencyID="SAR">' . number_format($invoice->sub_total ?? 0, 2, '.', '') . '</cbc:TaxableAmount>';
        $taxTotal .= '<cbc:TaxAmount currencyID="SAR">' . number_format($totalVat, 2, '.', '') . '</cbc:TaxAmount>';
        $taxTotal .= '<cac:TaxCategory>';
        $taxTotal .= '<cbc:ID>S</cbc:ID>'; // Standard rate
        $taxTotal .= '<cbc:Percent>15</cbc:Percent>';
        $taxTotal .= '<cac:TaxScheme>';
        $taxTotal .= '<cbc:ID>VAT</cbc:ID>';
        $taxTotal .= '</cac:TaxScheme>';
        $taxTotal .= '</cac:TaxCategory>';
        $taxTotal .= '</cac:TaxSubtotal>';
        $taxTotal .= '</cac:TaxTotal>';
        
        return $taxTotal;
    }

    /**
     * Build LegalMonetaryTotal section
     */
    protected function buildLegalMonetaryTotal(Invoice $invoice): string
    {
        $invoiceProducts = InvoiceProduct::where('invoice_id', $invoice->id)->get();
        $subTotal = 0;
        $totalVat = 0;
        
        foreach ($invoiceProducts as $product) {
            $lineTotal = $product->price * $product->quantity;
            $subTotal += $lineTotal;
            $totalVat += $lineTotal * 0.15;
        }
        
        $total = $subTotal + $totalVat;
        
        $monetary = '<cac:LegalMonetaryTotal>';
        $monetary .= '<cbc:LineExtensionAmount currencyID="SAR">' . number_format($subTotal, 2, '.', '') . '</cbc:LineExtensionAmount>';
        $monetary .= '<cbc:TaxExclusiveAmount currencyID="SAR">' . number_format($subTotal, 2, '.', '') . '</cbc:TaxExclusiveAmount>';
        $monetary .= '<cbc:TaxInclusiveAmount currencyID="SAR">' . number_format($total, 2, '.', '') . '</cbc:TaxInclusiveAmount>';
        $monetary .= '<cbc:PayableAmount currencyID="SAR">' . number_format($total, 2, '.', '') . '</cbc:PayableAmount>';
        $monetary .= '</cac:LegalMonetaryTotal>';
        
        return $monetary;
    }

    /**
     * Build InvoiceLines section
     */
    protected function buildInvoiceLines(Invoice $invoice): string
    {
        $lines = '';
        $invoiceProducts = InvoiceProduct::where('invoice_id', $invoice->id)->get();
        
        foreach ($invoiceProducts as $index => $product) {
            $lineId = $index + 1;
            $lineTotal = $product->price * $product->quantity;
            $lineVat = $lineTotal * 0.15;
            
            $lines .= '<cac:InvoiceLine>';
            $lines .= '<cbc:ID>' . $lineId . '</cbc:ID>';
            $lines .= '<cbc:InvoicedQuantity unitCode="PCE">' . $product->quantity . '</cbc:InvoicedQuantity>';
            $lines .= '<cbc:LineExtensionAmount currencyID="SAR">' . number_format($lineTotal, 2, '.', '') . '</cbc:LineExtensionAmount>';
            $lines .= '<cbc:TaxExclusiveAmount currencyID="SAR">' . number_format($lineTotal, 2, '.', '') . '</cbc:TaxExclusiveAmount>';
            $lines .= '<cbc:TaxInclusiveAmount currencyID="SAR">' . number_format($lineTotal + $lineVat, 2, '.', '') . '</cbc:TaxInclusiveAmount>';
            
            // TaxTotal for this line
            $lines .= '<cac:TaxTotal>';
            $lines .= '<cbc:TaxAmount currencyID="SAR">' . number_format($lineVat, 2, '.', '') . '</cbc:TaxAmount>';
            $lines .= '<cac:TaxSubtotal>';
            $lines .= '<cbc:TaxableAmount currencyID="SAR">' . number_format($lineTotal, 2, '.', '') . '</cbc:TaxableAmount>';
            $lines .= '<cbc:TaxAmount currencyID="SAR">' . number_format($lineVat, 2, '.', '') . '</cbc:TaxAmount>';
            $lines .= '<cac:TaxCategory>';
            $lines .= '<cbc:ID>S</cbc:ID>';
            $lines .= '<cbc:Percent>15</cbc:Percent>';
            $lines .= '<cac:TaxScheme>';
            $lines .= '<cbc:ID>VAT</cbc:ID>';
            $lines .= '</cac:TaxScheme>';
            $lines .= '</cac:TaxCategory>';
            $lines .= '</cac:TaxSubtotal>';
            $lines .= '</cac:TaxTotal>';
            
            // Item
            $lines .= '<cac:Item>';
            $lines .= '<cbc:Description>' . htmlspecialchars($product->product->name ?? 'Product') . '</cbc:Description>';
            $lines .= '<cac:CommodityClassification>';
            $lines .= '<cbc:ItemClassificationCode listID="CN">99999999</cbc:ItemClassificationCode>';
            $lines .= '</cac:CommodityClassification>';
            $lines .= '</cac:Item>';
            
            // Price
            $lines .= '<cac:Price>';
            $lines .= '<cbc:PriceAmount currencyID="SAR">' . number_format($product->price, 2, '.', '') . '</cbc:PriceAmount>';
            $lines .= '</cac:Price>';
            
            $lines .= '</cac:InvoiceLine>';
        }
        
        return $lines;
    }

    /**
     * Prepare invoice data for hashing
     */
    protected function prepareInvoiceData(Invoice $invoice, ZatcaConfiguration $config): array
    {
        return [
            'invoice_id' => $invoice->invoice_id,
            'issue_date' => $invoice->issue_date,
            'customer_id' => $invoice->customer_id,
            'sub_total' => $invoice->sub_total ?? 0,
            'total_amount' => $invoice->total_amount ?? 0,
            'tax_amount' => $invoice->total_tax ?? 0,
            'zatca_tax_number' => $config->zatca_tax_number,
        ];
    }

    /**
     * Determine invoice type code based on customer and configuration
     */
    protected function determineInvoiceTypeCode(Invoice $invoice, ?string $buyerTaxNumber): string
    {
        // B2B if buyer has tax number, B2C if not
        return $buyerTaxNumber ? '380' : '388'; // 380=Standard, 388=Simplified
    }

    /**
     * Get payment method code for ZATCA
     */
    protected function getPaymentMethodCode(string $paymentMethod): string
    {
        $paymentCodes = [
            'CASH' => '1',
            'BANK_TRANSFER' => '30',
            'CREDIT_CARD' => '48',
            'DEBIT_CARD' => '48',
            'CHECK' => '20',
            'OTHER' => '99',
        ];
        
        return $paymentCodes[strtoupper($paymentMethod)] ?? '99';
    }
}
