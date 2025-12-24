<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountantAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if this is an accountant impersonating a company
        if (session('accountant_impersonating') && Auth::check()) {
            // Define allowed accounting routes/controllers
            $allowedRoutes = [
                // Dashboard
                'dashboard',
                'account-dashboard',

                // Accounting module routes
                'customer.index', 'customer.create', 'customer.store', 'customer.edit', 'customer.update', 'customer.destroy',
                'vender.index', 'vender.create', 'vender.store', 'vender.edit', 'vender.update', 'vender.destroy',
                'proposal.index', 'proposal.create', 'proposal.store', 'proposal.edit', 'proposal.update', 'proposal.destroy',
                'invoice.index', 'invoice.create', 'invoice.store', 'invoice.edit', 'invoice.update', 'invoice.destroy',
                'revenue.index', 'revenue.create', 'revenue.store', 'revenue.edit', 'revenue.update', 'revenue.destroy',
                'credit-note', 'custom-credit.note', 'invoice.credit.note', 'invoice.edit.custom-credit', 'invoice.custom-note.edit',
                'bill.index', 'bill.create', 'bill.store', 'bill.edit', 'bill.update', 'bill.destroy',
                'expense.index', 'expense.create', 'expense.store', 'expense.edit', 'expense.update', 'expense.destroy',
                'payment.index', 'payment.create', 'payment.store', 'payment.edit', 'payment.update', 'payment.destroy',
                'debit-note', 'custom-debit.note', 'bill.debit.note', 'bill.edit.custom-debit', 'bill.custom-note.edit',
                'bank-account.index', 'bank-account.create', 'bank-account.store', 'bank-account.edit', 'bank-account.update', 'bank-account.destroy',
                'bank-transfer.index', 'bank-transfer.create', 'bank-transfer.store', 'bank-transfer.edit', 'bank-transfer.update', 'bank-transfer.destroy',
                'taxes.index', 'taxes.create', 'taxes.store', 'taxes.edit', 'taxes.update', 'taxes.destroy',
                'product-category.index', 'product-category.create', 'product-category.store', 'product-category.edit', 'product-category.update', 'product-category.destroy',
                'product-unit.index', 'product-unit.create', 'product-unit.store', 'product-unit.edit', 'product-unit.update', 'product-unit.destroy',
                'custom-field.index', 'custom-field.create', 'custom-field.store', 'custom-field.edit', 'custom-field.update', 'custom-field.destroy',
                'chart-of-account.index', 'chart-of-account.show',
                'journal-entry.index', 'journal-entry.create', 'journal-entry.store', 'journal-entry.edit', 'journal-entry.update', 'journal-entry.destroy',
                'transaction.index',
                'goal.index', 'goal.create', 'goal.store', 'goal.edit', 'goal.update', 'goal.destroy',
                'budget.index', 'budget.create', 'budget.store', 'budget.edit', 'budget.update', 'budget.destroy',

                // Reports
                'report.account.statement', 'report.invoice.summary', 'report.sales', 'report.receivables', 'report.payables',
                'report.bill.summary', 'report.product.stock.report', 'report.monthly.cashflow', 'report.quarterly.cashflow',
                'report.income.summary', 'report.expense.summary', 'report.income.vs.expense.summary', 'report.tax.summary',
                'report.ledger', 'report.balance.sheet', 'report.profit.loss', 'trial.balance',

                // Print settings
                'print.setting',

                // Profile and basic settings
                'profile', 'update.profile', 'update.password', 'change.mode',

                // Exit company
                'exit.company',
            ];

            $currentRoute = $request->route() ? $request->route()->getName() : '';

            // Allow if route is in allowed list or if it's a general route
            if (!in_array($currentRoute, $allowedRoutes)) {
                // Check if it's an accounting-related URL pattern
                $url = $request->getPathInfo();

                // Allow accounting-related URLs
                $allowedPatterns = [
                    '/customer',
                    '/vender',
                    '/proposal',
                    '/invoice',
                    '/revenue',
                    '/credit-note',
                    '/bill',
                    '/expense',
                    '/payment',
                    '/debit-note',
                    '/bank-account',
                    '/bank-transfer',
                    '/taxes',
                    '/product-category',
                    '/product-unit',
                    '/custom-field',
                    '/chart-of-account',
                    '/journal-entry',
                    '/transaction',
                    '/goal',
                    '/budget',
                    '/report',
                    '/print',
                ];

                $isAllowed = false;
                foreach ($allowedPatterns as $pattern) {
                    if (strpos($url, $pattern) === 0) {
                        $isAllowed = true;
                        break;
                    }
                }

                // Allow dashboard and profile
                if (strpos($url, '/account-dashboard') === 0 ||
                    strpos($url, '/profile') === 0 ||
                    strpos($url, '/exit-company') === 0 ||
                    $url === '/dashboard') {
                    $isAllowed = true;
                }

                if (!$isAllowed) {
                    return redirect()->route('dashboard')->with('error', 'Access restricted. Accountants can only access accounting features.');
                }
            }
        }

        return $next($request);
    }
}
