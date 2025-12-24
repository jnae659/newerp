<?php

namespace App\Services;

use App\Models\ZatcaConfiguration;
use App\Models\ZatcaInvoice;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ZatcaTaxService
{
    /**
     * Saudi Arabia VAT rates
     */
    protected $vatRates = [
        'standard' => 15.0,
        'zero_rated' => 0.0,
        'exempt' => 0.0,
    ];

    /**
     * Calculate VAT for an invoice line item
     */
    public function calculateVAT($amount, $vatRate = null, $isInclusive = false)
    {
        $rate = $vatRate ?? $this->vatRates['standard'];
        
        if ($isInclusive) {
            // VAT inclusive calculation
            $netAmount = $amount / (1 + ($rate / 100));
            $vatAmount = $amount - $netAmount;
        } else {
            // VAT exclusive calculation
            $netAmount = $amount;
            $vatAmount = $amount * ($rate / 100);
        }

        return [
            'net_amount' => round($netAmount, 2),
            'vat_rate' => $rate,
            'vat_amount' => round($vatAmount, 2),
            'total_amount' => round($netAmount + $vatAmount, 2),
        ];
    }

    /**
     * Generate tax report for ZATCA
     */
    public function generateTaxReport($companyId, $startDate, $endDate)
    {
        try {
            $invoices = ZatcaInvoice::where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('zatca_status', 'valid')
                ->with('invoice')
                ->get();

            $report = [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'summary' => [
                    'total_invoices' => $invoices->count(),
                    'total_net_amount' => 0,
                    'total_vat_amount' => 0,
                    'total_gross_amount' => 0,
                ],
                'vat_breakdown' => [
                    'standard_vat' => ['count' => 0, 'net' => 0, 'vat' => 0, 'gross' => 0],
                    'zero_vat' => ['count' => 0, 'net' => 0, 'vat' => 0, 'gross' => 0],
                    'exempt' => ['count' => 0, 'net' => 0, 'vat' => 0, 'gross' => 0],
                ],
                'invoices' => [],
            ];

            foreach ($invoices as $zatcaInvoice) {
                $invoiceData = $zatcaInvoice->zatca_data;
                $invoice = $zatcaInvoice->invoice;

                if (!$invoice) continue;

                $vatSummary = $this->extractVATSummary($invoiceData);
                
                // Update summary
                $report['summary']['total_invoices']++;
                $report['summary']['total_net_amount'] += $vatSummary['net_amount'];
                $report['summary']['total_vat_amount'] += $vatSummary['vat_amount'];
                $report['summary']['total_gross_amount'] += $vatSummary['total_amount'];

                // Update VAT breakdown
                $vatType = $this->determineVATType($vatSummary['vat_rate']);
                $report['vat_breakdown'][$vatType]['count']++;
                $report['vat_breakdown'][$vatType]['net'] += $vatSummary['net_amount'];
                $report['vat_breakdown'][$vatType]['vat'] += $vatSummary['vat_amount'];
                $report['vat_breakdown'][$vatType]['gross'] += $vatSummary['total_amount'];

                // Add invoice details
                $report['invoices'][] = [
                    'zatca_invoice_number' => $zatcaInvoice->zatca_invoice_number,
                    'invoice_number' => $invoice->invoice_id,
                    'customer_name' => $invoice->customer ? $invoice->customer->name : 'N/A',
                    'date' => $invoice->issue_date,
                    'net_amount' => $vatSummary['net_amount'],
                    'vat_amount' => $vatSummary['vat_amount'],
                    'total_amount' => $vatSummary['total_amount'],
                    'vat_rate' => $vatSummary['vat_rate'],
                ];
            }

            // Round all amounts
            $report['summary']['total_net_amount'] = round($report['summary']['total_net_amount'], 2);
            $report['summary']['total_vat_amount'] = round($report['summary']['total_vat_amount'], 2);
            $report['summary']['total_gross_amount'] = round($report['summary']['total_gross_amount'], 2);

            foreach ($report['vat_breakdown'] as $type => $data) {
                $report['vat_breakdown'][$type]['net'] = round($data['net'], 2);
                $report['vat_breakdown'][$type]['vat'] = round($data['vat'], 2);
                $report['vat_breakdown'][$type]['gross'] = round($data['gross'], 2);
            }

            return $report;

        } catch (\Exception $e) {
            throw new \Exception('Error generating tax report: ' . $e->getMessage());
        }
    }

    /**
     * Extract VAT summary from ZATCA invoice data
     */
    protected function extractVATSummary($zatcaData)
    {
        $totalNet = 0;
        $totalVat = 0;
        $totalGross = 0;
        $vatRate = $this->vatRates['standard'];

        if (isset($zatcaData['lines']) && is_array($zatcaData['lines'])) {
            foreach ($zatcaData['lines'] as $line) {
                $totalNet += $line['net_amount'] ?? 0;
                $totalVat += $line['vat_amount'] ?? 0;
                $totalGross += $line['total_amount'] ?? 0;
                
                if (isset($line['vat_rate'])) {
                    $vatRate = $line['vat_rate'];
                }
            }
        }

        return [
            'net_amount' => $totalNet,
            'vat_rate' => $vatRate,
            'vat_amount' => $totalVat,
            'total_amount' => $totalGross,
        ];
    }

    /**
     * Determine VAT type based on rate
     */
    protected function determineVATType($vatRate)
    {
        if ($vatRate == $this->vatRates['standard']) {
            return 'standard_vat';
        } elseif ($vatRate == $this->vatRates['zero_rated']) {
            return 'zero_vat';
        } else {
            return 'exempt';
        }
    }

    /**
     * Generate VAT return for ZATCA
     */
    public function generateVATReturn($companyId, $taxPeriod)
    {
        try {
            $startDate = Carbon::parse($taxPeriod . '-01')->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $report = $this->generateTaxReport($companyId, $startDate, $endDate);
            
            $vatReturn = [
                'tax_period' => $taxPeriod,
                'company_tax_number' => $this->getCompanyTaxNumber($companyId),
                'reporting_period' => [
                    'from' => $startDate->format('Y-m-d'),
                    'to' => $endDate->format('Y-m-d'),
                ],
                'supplies' => [
                    'domestic_standard_rate' => $report['vat_breakdown']['standard_vat'],
                    'domestic_zero_rate' => $report['vat_breakdown']['zero_vat'],
                    'exempt_supplies' => $report['vat_breakdown']['exempt'],
                ],
                'totals' => [
                    'total_supplies' => $report['summary']['total_gross_amount'],
                    'total_vat_due' => $report['summary']['total_vat_amount'],
                ],
                'declarations' => [
                    'total_output_vat' => $report['summary']['total_vat_amount'],
                    'total_input_vat' => 0, // Would need input VAT tracking
                    'net_vat_due' => $report['summary']['total_vat_amount'],
                ],
            ];

            return $vatReturn;

        } catch (\Exception $e) {
            throw new \Exception('Error generating VAT return: ' . $e->getMessage());
        }
    }

    /**
     * Get company tax number
     */
    protected function getCompanyTaxNumber($companyId)
    {
        $config = ZatcaConfiguration::where('company_id', $companyId)->first();
        return $config ? $config->zatca_tax_number : null;
    }

    /**
     * Validate tax configuration for Saudi Arabia
     */
    public function validateTaxConfiguration(ZatcaConfiguration $config)
    {
        $errors = [];

        if (empty($config->zatca_tax_number)) {
            $errors[] = 'Tax Number is required for Saudi Arabia';
        }

        if (empty($config->zatca_branch_code)) {
            $errors[] = 'Branch Code is required for ZATCA compliance';
        }

        // Validate tax number format for Saudi Arabia
        if (!empty($config->zatca_tax_number)) {
            if (!$this->validateSaudiTaxNumber($config->zatca_tax_number)) {
                $errors[] = 'Invalid Saudi Tax Number format';
            }
        }

        return $errors;
    }

    /**
     * Validate Saudi tax number format
     */
    protected function validateSaudiTaxNumber($taxNumber)
    {
        // Saudi tax number should be 15 digits
        return preg_match('/^\d{15}$/', $taxNumber);
    }

    /**
     * Get Saudi VAT rates
     */
    public function getSaudiVatRates()
    {
        return $this->vatRates;
    }

    /**
     * Format amount for ZATCA (2 decimal places)
     */
    public function formatAmount($amount)
    {
        return round($amount, 2);
    }

    /**
     * Get VAT category codes for ZATCA
     */
    public function getVATCategoryCodes()
    {
        return [
            'standard' => 'S', // Standard rate
            'zero_rated' => 'Z', // Zero rate
            'exempt' => 'E', // Exempt
            'no_vat' => 'O', // Out of scope
        ];
    }
}
