@if (!empty($userPlan) && $userPlan->account == 1 && \Auth::user()->type != 'accountant')
                    @if (Gate::check('manage budget plan') || Gate::check('income vs expense report') ||
                            Gate::check('manage goal') || Gate::check('manage constant tax') ||
                            Gate::check('manage constant category') || Gate::check('manage constant unit') ||
                            Gate::check('manage constant custom field') || Gate::check('manage print settings') ||
                            Gate::check('manage customer') || Gate::check('manage vender') ||
                            Gate::check('manage proposal') || Gate::check('manage bank account') ||
                            Gate::check('manage bank transfer') || Gate::check('manage invoice') ||
                            Gate::check('manage revenue') || Gate::check('manage credit note') ||
                            Gate::check('manage bill') || Gate::check('manage payment') ||
                            Gate::check('manage debit note') || Gate::check('manage chart of account') ||
                            Gate::check('manage journal entry') || Gate::check('balance sheet report') ||
                            Gate::check('ledger report') || Gate::check('trial balance report') )
                        <li
                            class="dash-item dash-hasmenu
                                        {{ Request::route()->getName() == 'print-setting' ||
                                        Request::segment(1) == 'customer' || Request::segment(1) == 'vender' ||
                                        Request::segment(1) == 'proposal' || Request::segment(1) == 'bank-account' ||
                                        Request::segment(1) == 'bank-transfer' || Request::segment(1) == 'invoice' ||
                                        Request::segment(1) == 'revenue' || Request::segment(1) == 'credit-note' ||
                                        Request::segment(1) == 'taxes' || Request::segment(1) == 'product-category' ||
                                        Request::segment(1) == 'product-unit' || Request::segment(1) == 'payment-method' ||
                                        Request::segment(1) == 'custom-field' || Request::segment(1) == 'chart-of-account-type' ||
                                        (Request::segment(1) == 'transaction' && Request::segment(2) != 'ledger' &&
                                            Request::segment(2) != 'balance-sheet-report' && Request::segment(2) != 'trial-balance') ||
                                        Request::segment(1) == 'goal' || Request::segment(1) == 'budget' ||
                                        Request::segment(1) == 'chart-of-account' || Request::segment(1) == 'journal-entry' ||
                                        Request::segment(2) == 'ledger' || Request::segment(2) == 'balance-sheet' ||
                                        Request::segment(2) == 'trial-balance' || Request::segment(2) == 'profit-loss' ||
                                        Request::segment(1) == 'bill' || Request::segment(1) == 'expense' ||
                                        Request::segment(1) == 'payment' || Request::segment(1) == 'debit-note' || (Request::route()->getName() == 'report.balance.sheet') || (Request::route()->getName() == 'trial-balance-report') ? ' active dash-trigger'
                                            : '' }}">
                            <a href="#!" class="dash-link"><span class="dash-micon"><i
                                        class="ti ti-box"></i></span><span
                                    class="dash-mtext">{{ __('Accounting System ') }}
                                </span><span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                            </a>
                            <ul class="dash-submenu">

                                @if (Gate::check('manage bank account') || Gate::check('manage bank transfer'))
                                    <li
                                        class="dash-item dash-hasmenu {{ Request::segment(1) == 'bank-account' || Request::segment(1) == 'bank-transfer' ? 'active dash-trigger' : '' }}">
                                        <a class="dash-link" href="#">{{ __('Banking') }}<span
                                                class="dash-arrow"><i data-feather="chevron-right"></i></span></a>
                                        <ul class="dash-submenu">
                                            <li
                                                class="dash-item {{ Request::route()->getName() == 'bank-account.index' || Request::route()->getName() == 'bank-account.create' || Request::route()->getName() == 'bank-account.edit' ? ' active' : '' }}">
                                                <a class="dash-link"
                                                    href="{{ route('bank-account.index') }}">{{ __('Account') }}</a>
                                            </li>
                                            <li
                                                class="dash-item {{ Request::route()->getName() == 'bank-transfer.index' || Request::route()->getName() == 'bank-transfer.create' || Request::route()->getName() == 'bank-transfer.edit' ? ' active' : '' }}">
                                                <a class="dash-link"
                                                    href="{{ route('bank-transfer.index') }}">{{ __('Transfer') }}</a>
                                            </li>
                                        </ul>
                                    </li>
                                @endif
                                @if (Gate::check('manage customer') ||
                                        Gate::check('manage proposal') ||
                                        Gate::check('manage invoice') ||
                                        Gate::check('manage revenue') ||
                                        Gate::check('manage credit note'))
                                    <li
                                        class="dash-item dash-hasmenu {{ Request::segment(1) == 'customer' || Request::segment(1) == 'proposal' || Request::segment(1) == 'invoice' || Request::segment(1) == 'revenue' || Request::segment(1) == 'credit-note' ? 'active dash-trigger' : '' }}">
                                        <a class="dash-link" href="#">{{ __('Sales') }}<span
                                                class="dash-arrow"><i data-feather="chevron-right"></i></span></a>
                                        <ul class="dash-submenu">
                                            @if (Gate::check('manage customer'))
                                                <li
                                                    class="dash-item {{ Request::segment(1) == 'customer' ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('customer.index') }}">{{ __('Customer') }}</a>
                                                </li>
                                            @endif
                                            @if (Gate::check('manage proposal'))
                                                <li
                                                    class="dash-item {{ Request::segment(1) == 'proposal' ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('proposal.index') }}">{{ __('Estimate') }}</a>
                                                </li>
                                            @endif
                                            @can('manage invoice')
                                                <li
                                                    class="dash-item {{ Request::route()->getName() == 'invoice.index' || Request::route()->getName() == 'invoice.create' || Request::route()->getName() == 'invoice.edit' || Request::route()->getName() == 'invoice.show' ? ' active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('invoice.index') }}">{{ __('Invoice') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage revenue')
                                                <li
                                                    class="dash-item {{ Request::route()->getName() == 'revenue.index' || Request::route()->getName() == 'revenue.create' || Request::route()->getName() == 'revenue.edit' ? ' active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('revenue.index') }}">{{ __('Revenue') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage credit note')
                                                <li
                                                    class="dash-item {{ Request::route()->getName() == 'credit.note' ? ' active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('custom-credit.note') }}">{{ __('Credit Note') }}</a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endif
                                @if (Gate::check('manage vender') ||
                                        Gate::check('manage bill') ||
                                        Gate::check('manage payment') ||
                                        Gate::check('manage debit note'))
                                    <li
                                        class="dash-item dash-hasmenu {{ Request::segment(1) == 'bill' || Request::segment(1) == 'vender' || Request::segment(1) == 'expense' || Request::segment(1) == 'payment' || Request::segment(1) == 'debit-note' ? 'active dash-trigger' : '' }}">
                                        <a class="dash-link" href="#">{{ __('Purchases') }}<span
                                                class="dash-arrow"><i data-feather="chevron-right"></i></span></a>
                                        <ul class="dash-submenu">
                                            @if (Gate::check('manage vender'))
                                                <li
                                                    class="dash-item {{ Request::segment(1) == 'vender' ? 'active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('vender.index') }}">{{ __('Suppiler') }}</a>
                                                </li>
                                            @endif
                                            @can('manage bill')
                                                <li
                                                    class="dash-item {{ Request::route()->getName() == 'bill.index' || Request::route()->getName() == 'bill.create' || Request::route()->getName() == 'bill.edit' || Request::route()->getName() == 'bill.show' ? ' active' : '' }}">
                                                    <a class="dash-link"
                                                    href="{{ route('bill.index') }}">{{ __('Bill') }}</a>
                                                </li>
                                                <li
                                                    class="dash-item {{ Request::route()->getName() == 'expense.index' || Request::route()->getName() == 'expense.create' || Request::route()->getName() == 'expense.edit' || Request::route()->getName() == 'expense.show' ? ' active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('expense.index') }}">{{ __('Expense') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage payment')
                                                <li
                                                    class="dash-item {{ Request::route()->getName() == 'payment.index' || Request::route()->getName() == 'payment.create' || Request::route()->getName() == 'payment.edit' ? ' active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('payment.index') }}">{{ __('Payment') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage debit note')
                                                <li
                                                    class="dash-item  {{ Request::route()->getName() == 'debit.note' ? ' active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('custom-debit.note') }}">{{ __('Debit Note') }}</a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endif
                                @if (Gate::check('manage chart of account') ||
                                        Gate::check('manage journal entry') ||
                                        Gate::check('ledger report') ||
                                        Gate::check('bill report') ||
                                        Gate::check('income vs expense report') ||
                                        Gate::check('trial balance report'))
                                    <li
                                        class="dash-item dash-hasmenu {{ Request::segment(1) == 'chart-of-account' ||
                                        Request::segment(1) == 'journal-entry' ||
                                        Request::segment(2) == 'profit-loss' ||
                                        Request::segment(2) == 'ledger' ||
                                        Request::segment(2) == 'trial-balance-report' ||
                                        Request::segment(2) == 'balance-sheet-report' ||
                                        Request::segment(2) == 'trial-balance' || (Request::route()->getName() == 'report.balance.sheet') || (Request::route()->getName() == 'trial-balance-report') ? 'active dash-trigger'
                                            : '' }}">
                                        <a class="dash-link" href="#">{{ __('Double Entry') }}<span
                                                class="dash-arrow"><i data-feather="chevron-right"></i></span></a>
                                        <ul class="dash-submenu">
                                            @can('manage chart of account')
                                                <li
                                                    class="dash-item {{ Request::route()->getName() == 'chart-of-account.index' || Request::route()->getName() == 'chart-of-account.show' ? ' active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('chart-of-account.index') }}">{{ __('Chart of Accounts') }}</a>
                                                </li>
                                            @endcan
                                            @can('manage journal entry')
                                                <li
                                                    class="dash-item {{ Request::route()->getName() == 'journal-entry.edit' ||
                                                    Request::route()->getName() == 'journal-entry.create' ||
                                                    Request::route()->getName() == 'journal-entry.index' ||
                                                    Request::route()->getName() == 'journal-entry.show'
                                                        ? ' active'
                                                        : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('journal-entry.index') }}">{{ __('Journal Account') }}</a>
                                                </li>
                                            @endcan
                                            @can('ledger report')
                                                <li
                                                    class="dash-item {{ Request::route()->getName() == 'report.ledger' ? ' active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('report.ledger', 0) }}">{{ __('Ledger Summary') }}</a>
                                                </li>
                                            @endcan
                                            @can('bill report')
                                                <li
                                                    class="dash-item {{ Request::route()->getName() == 'report.balance.sheet' ? ' active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('report.balance.sheet') }}">{{ __('Balance Sheet') }}</a>
                                                </li>
                                            @endcan
                                            @can('income vs expense report')
                                                <li
                                                    class="dash-item {{ Request::route()->getName() == 'report.profit.loss' ? ' active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('report.profit.loss') }}">{{ __('Profit & Loss') }}</a>
                                                </li>
                                            @endcan
                                            @can('trial balance report')
                                                <li
                                                    class="dash-item {{ Request::route()->getName() == 'trial.balance' || (Request::route()->getName() == 'trial-balance-report') ? ' active' : '' }}">
                                                    <a class="dash-link"
                                                        href="{{ route('trial.balance') }}">{{ __('Trial Balance') }}</a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endif
                                @if (\Auth::user()->type == 'company')
                                    <li class="dash-item {{ Request::segment(1) == 'budget' ? 'active' : '' }}">
                                        <a class="dash-link"
                                            href="{{ route('budget.index') }}">{{ __('Budget Planner') }}</a>
                                    </li>
                                @endif
                                @if (Gate::check('manage goal'))
                                    <li class="dash-item {{ Request::segment(1) == 'goal' ? 'active' : '' }}">
                                        <a class="dash-link"
                                            href="{{ route('goal.index') }}">{{ __('Financial Goal') }}</a>
                                    </li>
                                @endif
                                @if (Gate::check('manage constant tax') ||
                                        Gate::check('manage constant category') ||
                                        Gate::check('manage constant unit') ||
                                        Gate::check('manage constant custom field'))
                                    <li
                                        class="dash-item {{ Request::segment(1) == 'taxes' || Request::segment(1) == 'product-category' || Request::segment(1) == 'product-unit' || Request::segment(1) == 'payment-method' || Request::segment(1) == 'custom-field' || Request::segment(1) == 'chart-of-account-type' ? 'active dash-trigger' : '' }}">
                                        <a class="dash-link"
                                            href="{{ route('taxes.index') }}">{{ __('Accounting Setup') }}</a>
                                    </li>
                                @endif

                                {{-- ZATCA Integration for Saudi Arabia --}}
                                @if(\Auth::user()->country == 'SA')
                                    <li class="dash-item {{ Request::segment(1) == 'zatca' ? 'active dash-trigger' : '' }}">
                                        <a class="dash-link" href="{{ route('zatca.configuration') }}">
                                            <span class="dash-micon">
                                                <i class="ti ti-shield-check"></i>
                                            </span>
                                            <span class="dash-mtext">{{ __('ZATCA Integration') }}</span>
                                        </a>
                                    </li>
                                @endif

                                @if (Gate::check('manage print settings'))
                                    <li
                                        class="dash-item {{ Request::route()->getName() == 'print-setting' ? ' active' : '' }}">
                                        <a class="dash-link"
                                            href="{{ route('print.setting') }}">{{ __('Print Settings') }}</a>
                                    </li>
                                @endif

                            </ul>
                        </li>
                    @endif
                @endif