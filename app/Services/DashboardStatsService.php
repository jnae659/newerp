<?php

namespace App\Services;

use App\Models\User;
use App\Models\Customer;
use App\Models\Vender;
use App\Models\Invoice;
use App\Models\Bill;
use App\Models\Revenue;
use App\Models\Payment;
use App\Models\Order;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardStatsService
{
    public function countUsers($user)
    {
        return User::where('type', '!=', 'super admin')->where('type', '!=', 'company')->where('type', '!=', 'client')->where('created_by', '=', $user->creatorId())->count();
    }

    public function countCompany($user)
    {
        return User::where('type', '=', 'company')->where('created_by', '=', $user->creatorId())->count();
    }

    public function countOrder()
    {
        return Order::count();
    }

    public function countplan()
    {
        return Plan::count();
    }

    public function countPaidCompany($user)
    {
        return User::where('type', '=', 'company')->whereNotIn(
            'plan', [
                0,
                1,
            ]
        )->where('created_by', '=', $user->id)->count();
    }

    public function countCustomers($user)
    {
        return Customer::where('created_by', '=', $user->creatorId())->count();
    }

    public function countVenders($user)
    {
        return Vender::where('created_by', '=', $user->creatorId())->count();
    }

    public function countInvoices($user)
    {
        return Invoice::where('created_by', '=', $user->creatorId())->count();
    }

    public function countBills($user)
    {
        return Bill::where('created_by', '=', $user->creatorId())->count();
    }

    public function todayIncome($user)
    {
        $revenue = Revenue::where('created_by', '=', $user->creatorId())->whereRaw('Date(date) = CURDATE()')->where('created_by', Auth::user()->creatorId())->sum('amount');
        $invoiceTotal = $this->getInvoiceProductsData((date('y-m-d')));

        $totalIncome = (!empty($revenue) ? $revenue : 0) + (!empty($invoiceTotal) ? ($invoiceTotal) : 0);

        return $totalIncome;
    }

    public function todayExpense($user)
    {
        $payment = Payment::where('created_by', '=', $user->creatorId())->where('created_by', Auth::user()->creatorId())->whereRaw('Date(date) = CURDATE()')->sum('amount');

        $billTotal = $this->getBillProductsData(date('y-m-d'));

        $totalExpense = (!empty($payment) ? $payment : 0) + (!empty($billTotal) ? ($billTotal) : 0);

        return $totalExpense;
    }

    public function incomeCurrentMonth($user)
    {
        $currentMonth = date('m');
        $revenue = Revenue::where('created_by', '=', $user->creatorId())->whereRaw('MONTH(date) = ?', [$currentMonth])->sum('amount');
        $invoiceTotal = $this->getInvoiceProductsData('', $currentMonth);

        $totalIncome = (!empty($revenue) ? $revenue : 0) + (!empty($invoiceTotal) ? ($invoiceTotal) : 0);

        return $totalIncome;
    }

    public function incomecat($user)
    {
        $currentMonth = date('m');
        $revenue = Revenue::where('created_by', '=', $user->creatorId())->whereRaw('MONTH(date) = ?', [$currentMonth])->sum('amount');

        $incomes = Revenue::selectRaw('sum(revenues.amount) as amount,MONTH(date) as month,YEAR(date) as year,category_id')->leftjoin('product_service_categories', 'revenues.category_id', '=', 'product_service_categories.id')->where('product_service_categories.type', '=', 1);

        $invoices = Invoice::select('*')->where('created_by', Auth::user()->creatorId())->whereRAW('MONTH(send_date) = ?', [$currentMonth])->get();

        $invoiceArray = array();
        foreach ($invoices as $invoice) {
            $invoiceArray[] = $invoice->getTotal();
        }
        $totalIncome = (!empty($revenue) ? $revenue : 0) + (!empty($invoiceArray) ? array_sum($invoiceArray) : 0);

        return $totalIncome;
    }

    public function expenseCurrentMonth($user)
    {
        $currentMonth = date('m');

        $payment = Payment::where('created_by', '=', $user->creatorId())->whereRaw('MONTH(date) = ?', [$currentMonth])->sum('amount');
        $billTotal = $this->getBillProductsData('', $currentMonth);

        $totalExpense = (!empty($payment) ? $payment : 0) + (!empty($billTotal) ? ($billTotal) : 0);

        return $totalExpense;
    }

    public static function getInvoiceProductsData($date = '', $month = '')
    {
        if ($month != '' && $date != '') {
            $InvoiceProducts = \DB::table('invoice_products')
                ->select('invoice_products.invoice_id as invoice',
                    \DB::raw('SUM(quantity) as total_quantity'),
                    \DB::raw('SUM(discount) as total_discount'),
                    \DB::raw('SUM(price * quantity)  as sub_total'))
                ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM invoice_products
                    LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
                    WHERE invoice_products.invoice_id = invoices.id) as tax_values')
                ->leftJoin('invoices', 'invoice_products.invoice_id', 'invoices.id')
                ->where(\DB::raw('YEAR(invoices.send_date)'), '=', $date)
                ->where(\DB::raw('MONTH(invoices.send_date)'), '=', $month)
                ->where('invoices.created_by', Auth::user()->creatorId())
                ->groupBy('invoice')
                ->get()
                ->keyBy('invoice');
        } elseif ($date != '') {
            $InvoiceProducts = \DB::table('invoice_products')
                ->select('invoice_products.invoice_id as invoice',
                    \DB::raw('SUM(quantity) as total_quantity'),
                    \DB::raw('SUM(discount) as total_discount'),
                    \DB::raw('SUM(price * quantity)  as sub_total'))
                ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM invoice_products
                    LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
                    WHERE invoice_products.invoice_id = invoices.id) as tax_values')
                ->leftJoin('invoices', 'invoice_products.invoice_id', 'invoices.id')
                ->where(\DB::raw('(invoices.send_date)'), '=', $date)
                ->where('invoices.created_by', Auth::user()->creatorId())
                ->groupBy('invoice')
                ->get()
                ->keyBy('invoice');
        } elseif ($month != '') {
            $InvoiceProducts = \DB::table('invoice_products')
                ->select('invoice_products.invoice_id as invoice',
                    \DB::raw('SUM(quantity) as total_quantity'),
                    \DB::raw('SUM(discount) as total_discount'),
                    \DB::raw('SUM(price * quantity)  as sub_total'))
                ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM invoice_products
                    LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
                    WHERE invoice_products.invoice_id = invoices.id) as tax_values')
                ->leftJoin('invoices', 'invoice_products.invoice_id', 'invoices.id')
                ->where(\DB::raw('MONTH(invoices.send_date)'), '=', $month)
                ->where('invoices.created_by', Auth::user()->creatorId())
                ->groupBy('invoice')
                ->get()
                ->keyBy('invoice');
        }

        $InvoiceProducts->map(function ($invoice) {
            $invoice->total = $invoice->sub_total + $invoice->tax_values - $invoice->total_discount;
            return $invoice;
        });

        $total = 0;
        foreach ($InvoiceProducts as $invoice) {
            $total += ($invoice->total);
        }

        return $total;
    }

    public static function getBillProductsData($date = '', $month = '')
    {
        if ($month != '' && $date != '') {
            $BillProducts = \DB::table('bill_products')
                ->select('bill_products.bill_id as bill',
                    \DB::raw('SUM(bill_products.quantity) as total_quantity'),
                    \DB::raw('SUM(bill_products.discount) as total_discount'),
                    \DB::raw('SUM(bill_products.price * bill_products.quantity)  as sub_total'))
                ->selectRaw('(SELECT SUM(bill_accounts.price) FROM bill_accounts
                    WHERE bill_accounts.ref_id = bills.id) as acc_price')
                ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM bill_products
                                        LEFT JOIN taxes ON FIND_IN_SET(taxes.id, bill_products.tax) > 0
                                        WHERE bill_products.bill_id = bills.id) as tax_values')
                ->leftJoin('bills', 'bill_products.bill_id', 'bills.id')
                ->where(\DB::raw('YEAR(bills.send_date)'), '=', $date)
                ->where(\DB::raw('MONTH(bills.send_date)'), '=', $month)
                ->where('bills.created_by', Auth::user()->creatorId())
                ->groupBy('bill')
                ->get()
                ->keyBy('bill');
        } elseif ($date != '') {
            $BillProducts = \DB::table('bill_products')
                ->select('bill_products.bill_id as bill',
                    \DB::raw('SUM(quantity) as total_quantity'),
                    \DB::raw('SUM(discount) as total_discount'),
                    \DB::raw('SUM(bill_products.price * bill_products.quantity)  as sub_total'))
                ->selectRaw('(SELECT SUM(bill_accounts.price) FROM bill_accounts
                    WHERE bill_accounts.ref_id = bills.id) as acc_price')
                ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM bill_products
                                LEFT JOIN taxes ON FIND_IN_SET(taxes.id, bill_products.tax) > 0
                                WHERE bill_products.bill_id = bills.id) as tax_values')
                ->leftJoin('bills', 'bill_products.bill_id', 'bills.id')
                ->where(\DB::raw('(bills.send_date)'), '=', $date)
                ->where('bills.created_by', Auth::user()->creatorId())
                ->groupBy('bill')
                ->get()
                ->keyBy('bill');
        } elseif ($month != '') {
            $BillProducts = \DB::table('bill_products')
                ->select('bill_products.bill_id as bill',
                    \DB::raw('SUM(quantity) as total_quantity'),
                    \DB::raw('SUM(discount) as total_discount'),
                    \DB::raw('SUM(bill_products.price * bill_products.quantity)  as sub_total'))
                ->selectRaw('(SELECT SUM(bill_accounts.price) FROM bill_accounts
                    WHERE bill_accounts.ref_id = bills.id) as acc_price')
                ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM bill_products
                                LEFT JOIN taxes ON FIND_IN_SET(taxes.id, bill_products.tax) > 0
                                WHERE bill_products.bill_id = bills.id) as tax_values')
                ->leftJoin('bills', 'bill_products.bill_id', 'bills.id')
                ->where(\DB::raw('MONTH(bills.send_date)'), '=', $month)
                ->where('bills.created_by', Auth::user()->creatorId())
                ->groupBy('bill')
                ->get()
                ->keyBy('bill');
        }
        $BillProducts->map(function ($bill) {
            $bill->total = $bill->sub_total + $bill->acc_price + $bill->tax_values - $bill->total_discount;
            return $bill;
        });
        $total = 0;
        foreach ($BillProducts as $bill) {
            $total += ($bill->total);
        }

        return $total;
    }

    public function getincExpBarChartData($user)
    {
        $month[] = __('January');
        $month[] = __('February');
        $month[] = __('March');
        $month[] = __('April');
        $month[] = __('May');
        $month[] = __('June');
        $month[] = __('July');
        $month[] = __('August');
        $month[] = __('September');
        $month[] = __('October');
        $month[] = __('November');
        $month[] = __('December');
        $dataArr['month'] = $month;

        $user_id = Auth::user()->creatorId();
        for ($i = 1; $i <= 12; $i++) {
            $monthlyIncome = Revenue::selectRaw('sum(amount) amount')->where('created_by', '=', $user_id)->whereRaw('year(`date`) = ?', array(date('Y')))->whereRaw('month(`date`) = ?', $i)->first();
            $invoiceTotal = $this->getInvoiceProductsData((date('Y')), $i);

            $totalIncome = (!empty($monthlyIncome) ? $monthlyIncome->amount : 0) + (!empty($invoiceTotal) ? ($invoiceTotal) : 0);

            $incomeArr[] = !empty($totalIncome) ? str_replace(",", "", number_format($totalIncome, 2)) : 0;

            $monthlyExpense = Payment::selectRaw('sum(amount) amount')->where('created_by', '=', $user->creatorId())->whereRaw('year(`date`) = ?', array(date('Y')))->whereRaw('month(`date`) = ?', $i)->first();
            $billTotal = $this->getBillProductsData((date('Y')), $i);

            $totalExpense = (!empty($monthlyExpense) ? $monthlyExpense->amount : 0) + (!empty($billTotal) ? ($billTotal) : 0);

            $expenseArr[] = !empty($totalExpense) ? str_replace(",", "", number_format($totalExpense, 2)) : 0;
        }

        $dataArr['income'] = $incomeArr;
        $dataArr['expense'] = $expenseArr;

        return $dataArr;
    }

    public function getIncExpLineChartDate($user)
    {
        $usr = Auth::user();
        $m = date("m");
        $de = date("d");
        $y = date("Y");
        $format = 'Y-m-d';
        $arrDate = [];
        $arrDateFormat = [];

        for ($i = 0; $i <= 15 - 1; $i++) {
            $date = date($format, mktime(0, 0, 0, $m, ($de - $i), $y));

            $arrDay[] = date('D', mktime(0, 0, 0, $m, ($de - $i), $y));
            $arrDate[] = $date;
            $arrDateFormat[] = date("d-M", strtotime($date));
        }
        $dataArr['day'] = $arrDateFormat;
        for ($i = 0; $i < count($arrDate); $i++) {
            $dayIncome = Revenue::selectRaw('sum(amount) amount')->where('created_by', Auth::user()->creatorId())->whereRaw('date = ?', $arrDate[$i])->first();

            $invoiceTotal = $this->getInvoiceProductsData($arrDate[$i]);

            $incomeAmount = (!empty($dayIncome->amount) ? $dayIncome->amount : 0) + (!empty($invoiceTotal) ? ($invoiceTotal) : 0);
            $incomeArr[] = str_replace(",", "", number_format($incomeAmount, 2));

            $dayExpense = Payment::selectRaw('sum(amount) amount')->where('created_by', Auth::user()->creatorId())->whereRaw('date = ?', $arrDate[$i])->first();

            $billTotal = $this->getBillProductsData($arrDate[$i]);

            $expenseAmount = (!empty($dayExpense->amount) ? $dayExpense->amount : 0) + (!empty($billTotal) ? ($billTotal) : 0);
            $expenseArr[] = str_replace(",", "", number_format($expenseAmount, 2));
        }

        $dataArr['income'] = $incomeArr;
        $dataArr['expense'] = $expenseArr;

        return $dataArr;
    }

    public function totalCompanyUser($id)
    {
        return User::where('created_by', '=', $id)->count();
    }

    public function totalCompanyCustomer($id)
    {
        return Customer::where('created_by', '=', $id)->count();
    }

    public function totalCompanyVender($id)
    {
        return Vender::where('created_by', '=', $id)->count();
    }

    public function planPrice($user)
    {
        if ($user->type == 'super admin') {
            $userId = $user->id;
        } else {
            $userId = $user->created_by;
        }

        return DB::table('settings')->where('created_by', '=', $userId)->get()->pluck('value', 'name');
    }

    public function invoicesData($user, $start, $current)
    {
        $InvoiceProducts = Invoice::select('invoices.invoice_id as invoice')
            ->selectRaw('sum((invoice_products.price * invoice_products.quantity) - invoice_products.discount) as price')
            ->selectRaw('(SELECT SUM(credit_notes.amount) FROM credit_notes WHERE credit_notes.invoice = invoices.id) as credit_price')
            ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM invoice_products
             LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
             WHERE invoice_products.invoice_id = invoices.id) as total_tax')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'invoices.id')
            ->where('issue_date', '>=', $start)->where('issue_date', '<=', $current)
            ->where('invoices.created_by', Auth::user()->creatorId())
            ->groupBy('invoice')
            ->get()
            ->keyBy('invoice')
            ->toArray();

        $invoicepayment = Invoice::select('invoices.invoice_id as invoice')
            ->selectRaw('sum((invoice_payments.amount)) as pay_price')
            ->leftJoin('invoice_payments', 'invoice_payments.invoice_id', 'invoices.id')
            ->where('issue_date', '>=', $start)->where('issue_date', '<=', $current)
            ->where('invoices.created_by', Auth::user()->creatorId())
            ->groupBy('invoice')
            ->get()
            ->keyBy('invoice')
            ->toArray();

        $mergedArray = [];

        foreach ($InvoiceProducts as $key => $value) {
            if (isset($invoicepayment[$key])) {
                $mergedArray[$key] = array_merge($value, $invoicepayment[$key]);
            }
        }

        $invoiceTotal = 0;
        $invoicePaid = 0;
        $invoiceDue = 0;
        $invoiceData = [];

        foreach ($mergedArray as $invoice) {
            $invoiceTotal += $invoice['price'] + $invoice['total_tax'];
            $invoicePaid += $invoice['pay_price'] + $invoice['credit_price'];
            $invoiceDue += ($invoice['price'] + $invoice['total_tax']) - $invoice['credit_price'] - $invoice['pay_price'];
        }

        $invoiceData = [
            "invoiceTotal" => $invoiceTotal,
            "invoicePaid" => $invoicePaid,
            'invoiceDue' => $invoiceDue,
        ];

        return $invoiceData;
    }

    public function billsData($user, $start, $current)
    {
        $billProducts = Bill::select('bills.bill_id as bill')
            ->selectRaw('sum((bill_products.price * bill_products.quantity) - bill_products.discount) as price')
            ->selectRaw('(SELECT SUM(debit_notes.amount) FROM debit_notes
             WHERE debit_notes.bill = bills.id) as debit_price')
            ->selectRaw('(SELECT SUM(bill_accounts.price) FROM bill_accounts
             WHERE bill_accounts.ref_id = bills.id) as acc_price')
            ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM bill_products
             LEFT JOIN taxes ON FIND_IN_SET(taxes.id, bill_products.tax) > 0
             WHERE bill_products.bill_id = bills.id) as total_tax')
            ->leftJoin('bill_products', 'bill_products.bill_id', 'bills.id')
            ->where('bill_date', '>=', $start)->where('bill_date', '<=', $current)
            ->where('bills.created_by', Auth::user()->creatorId())
            ->where('bills.type', 'Bill')
            ->groupBy('bill')
            ->get()
            ->keyBy('bill')
            ->toArray();

        $billPayment = Bill::select('bills.bill_id as bill')
            ->selectRaw('sum((bill_payments.amount)) as pay_price')
            ->leftJoin('bill_payments', 'bill_payments.bill_id', 'bills.id')
            ->where('bill_date', '>=', $start)->where('bill_date', '<=', $current)
            ->where('bills.created_by', Auth::user()->creatorId())
            ->where('bills.type', 'Bill')
            ->groupBy('bill')
            ->get()
            ->keyBy('bill')
            ->toArray();

        $mergedArray = [];

        foreach ($billProducts as $key => $value) {
            if (isset($billPayment[$key])) {
                $mergedArray[$key] = array_merge($value, $billPayment[$key]);
            } else {
                $mergedArray[$key] = ($value);
            }
        }

        $billTotal = 0;
        $billPaid = 0;
        $billDue = 0;
        $billData = [];

        foreach ($mergedArray as $bill) {
            $billTotal += $bill['price'] + $bill['total_tax'] + $bill['acc_price'];
            $billPaid += $bill['pay_price'] + $bill['debit_price'];
            $billDue += ($bill['price'] + $bill['total_tax'] + $bill['acc_price']) - $bill['pay_price'] - $bill['debit_price'];
        }

        $billData = [
            "billTotal" => $billTotal,
            "billPaid" => $billPaid,
            'billDue' => $billDue,
        ];

        return $billData;
    }

    public function expenseData($user, $start, $current)
    {
        $billProducts = Bill::select('bills.bill_id as bill')
            ->selectRaw('sum((bill_products.price * bill_products.quantity) - bill_products.discount) as price')
            ->selectRaw('(SELECT SUM(debit_notes.amount) FROM debit_notes
             WHERE debit_notes.bill = bills.id) as debit_price')
            ->selectRaw('(SELECT SUM(bill_accounts.price) FROM bill_accounts
             WHERE bill_accounts.ref_id = bills.id) as acc_price')
            ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM bill_products
             LEFT JOIN taxes ON FIND_IN_SET(taxes.id, bill_products.tax) > 0
             WHERE bill_products.bill_id = bills.id) as total_tax')
            ->leftJoin('bill_products', 'bill_products.bill_id', 'bills.id')
            ->where('bill_date', '>=', $start)->where('bill_date', '<=', $current)
            ->where('bills.created_by', Auth::user()->creatorId())
            ->where('bills.type', 'Expense')
            ->groupBy('bill')
            ->get()
            ->keyBy('bill')
            ->toArray();

        $billPayment = Bill::select('bills.bill_id as bill')
            ->selectRaw('sum((bill_payments.amount)) as pay_price')
            ->leftJoin('bill_payments', 'bill_payments.bill_id', 'bills.id')
            ->where('bill_date', '>=', $start)->where('bill_date', '<=', $current)
            ->where('bills.created_by', Auth::user()->creatorId())
            ->where('bills.type', 'Expense')
            ->groupBy('bill')
            ->get()
            ->keyBy('bill')
            ->toArray();

        $mergedArray = [];

        foreach ($billProducts as $key => $value) {
            if (isset($billPayment[$key])) {
                $mergedArray[$key] = array_merge($value, $billPayment[$key]);
            } else {
                $mergedArray[$key] = ($value);
            }
        }

        $billTotal = 0;
        $billPaid = 0;
        $billDue = 0;
        $billData = [];

        foreach ($mergedArray as $bill) {
            $billTotal += $bill['price'] + $bill['total_tax'] + $bill['acc_price'];
            $billPaid += $bill['pay_price'] + $bill['debit_price'];
            $billDue += ($bill['price'] + $bill['total_tax'] + $bill['acc_price']) - $bill['pay_price'] - $bill['debit_price'];
        }

        $billData = [
            "billTotal" => $billTotal,
            "billPaid" => $billPaid,
            'billDue' => $billDue,
        ];

        return $billData;
    }

    public function weeklyInvoice($user)
    {
        $staticstart = date('Y-m-d', strtotime('last Week'));
        $currentDate = date('Y-m-d');
        $invoices = $this->invoicesData($user, $staticstart, $currentDate);

        $invoiceDetail['invoiceTotal'] = $invoices['invoiceTotal'];
        $invoiceDetail['invoicePaid'] = $invoices['invoicePaid'];
        $invoiceDetail['invoiceDue'] = $invoices['invoiceDue'];

        return $invoiceDetail;
    }

    public function monthlyInvoice($user)
    {
        $staticstart = date('Y-m-d', strtotime('last Month'));
        $currentDate = date('Y-m-d');
        $invoices = $this->invoicesData($user, $staticstart, $currentDate);

        $invoiceDetail['invoiceTotal'] = $invoices['invoiceTotal'];
        $invoiceDetail['invoicePaid'] = $invoices['invoicePaid'];
        $invoiceDetail['invoiceDue'] = $invoices['invoiceDue'];

        return $invoiceDetail;
    }

    public function weeklyBill($user)
    {
        $staticstart = date('Y-m-d', strtotime('last Week'));
        $currentDate = date('Y-m-d');
        $bills = $this->billsData($user, $staticstart, $currentDate);
        $expense = $this->expenseData($user, $staticstart, $currentDate);

        $billDetail['billTotal'] = $bills['billTotal'] + $expense['billTotal'];
        $billDetail['billPaid'] = $bills['billPaid'] + $expense['billPaid'];
        $billDetail['billDue'] = $bills['billDue'] + $expense['billDue'];
        return $billDetail;
    }

    public function monthlyBill($user)
    {
        $staticstart = date('Y-m-d', strtotime('last Month'));
        $currentDate = date('Y-m-d');
        $bills = $this->billsData($user, $staticstart, $currentDate);
        $expense = $this->expenseData($user, $staticstart, $currentDate);

        $billDetail['billTotal'] = $bills['billTotal'] + $expense['billTotal'];
        $billDetail['billPaid'] = $bills['billPaid'] + $expense['billPaid'];
        $billDetail['billDue'] = $bills['billDue'] + $expense['billDue'];
        return $billDetail;
    }
}
