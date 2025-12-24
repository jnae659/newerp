@if (Gate::check('show hrm dashboard') ||
                        Gate::check('show project dashboard') ||
                        Gate::check('show account dashboard') ||
                        Gate::check('show crm dashboard') ||
                        Gate::check('show pos dashboard'))
                    <li
                        class="dash-item dash-hasmenu
                                {{ Request::segment(1) == null ||
                                Request::segment(1) == 'account-dashboard' ||
                                Request::segment(1) == 'hrm-dashboard' ||
                                Request::segment(1) == 'crm-dashboard' ||
                                Request::segment(1) == 'project-dashboard' ||
                                Request::segment(1) == 'account-statement-report' ||
                                Request::segment(1) == 'invoice-summary' ||
                                Request::segment(1) == 'sales' ||
                                Request::segment(1) == 'receivables' ||
                                Request::segment(1) == 'payables' ||
                                Request::segment(1) == 'bill-summary' ||
                                Request::segment(1) == 'product-stock-report' ||
                                Request::segment(1) == 'transaction' ||
                                Request::segment(1) == 'income-summary' ||
                                Request::segment(1) == 'expense-summary' ||
                                Request::segment(1) == 'income-vs-expense-summary' ||
                                Request::segment(1) == 'tax-summary' ||
                                Request::segment(1) == 'income report' ||
                                Request::segment(1) == 'report' ||
                                Request::segment(1) == 'reports-monthly-cashflow' ||
                                Request::segment(1) == 'reports-quarterly-cashflow' ||
                                Request::segment(1) == 'reports-payroll' ||
                                Request::segment(1) == 'report-leave' ||
                                Request::segment(1) == 'reports-monthly-attendance' ||
                                Request::segment(1) == 'reports-lead' ||
                                Request::segment(1) == 'reports-deal' ||
                                Request::segment(1) == 'pos-dashboard' ||
                                Request::segment(1) == 'reports-warehouse' ||
                                Request::segment(1) == 'reports-daily-purchase' ||
                                Request::segment(1) == 'reports-monthly-purchase' ||
                                Request::segment(1) == 'reports-daily-pos' ||
                                Request::segment(1) == 'reports-monthly-pos' ||
                                Request::segment(1) == 'reports-pos-vs-purchase'
                                    ? 'active dash-trigger'
                                    : '' }}">
                        <a href="{{ \Auth::user()->type == 'accountant' ? route('dashboard') : '#!' }}" class="dash-link ">
                            <span class="dash-micon">
                                <i class="ti ti-home"></i>
                            </span>
                            <span class="dash-mtext">{{ __('Dashboard') }}</span>
                            @if(\Auth::user()->type != 'accountant')
                                <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                            @endif
                        </a>
                        <ul class="dash-submenu">
                            @if (!empty($userPlan) && $userPlan->account == 1 && Gate::check('show account dashboard') && \Auth::user()->type != 'accountant')
                                <li
                                    class="dash-item dash-hasmenu {{ Request::segment(1) == null || Request::segment(1) == 'account-dashboard' || Request::segment(1) == 'report' || Request::segment(1) == 'reports-monthly-cashflow' || Request::segment(1) == 'reports-quarterly-cashflow' ? ' active dash-trigger' : '' }}">
                                    <a class="dash-link" href="#">{{ __('Accounting ') }}<span
                                            class="dash-arrow"><i data-feather="chevron-right"></i></span></a>
                                    <ul class="dash-submenu">
                                        @can('show account dashboard')
                                            <li
                                                class="dash-item {{ Request::segment(1) == null || Request::segment(1) == 'account-dashboard' ? ' active' : '' }}">
                                                <a class="dash-link"
                                                    href="{{ route('dashboard') }}">{{ __(' Overview') }}</a>
                                            </li>
                                        @endcan
                                        @if (Gate::check('income report') ||
                                                Gate::check('expense report') ||
                                                Gate::check('income vs expense report') ||
                                                Gate::check('tax report') ||
                                                Gate::check('loss & profit report') ||
                                                Gate::check('bill report') ||
                                                Gate::check('stock report') ||
                                                Gate::check('invoice report') ||
                                                Gate::check('manage transaction') ||
                                                Gate::check('statement report'))
                                            <li
                                                class="dash-item dash-hasmenu {{ Request::segment(1) == 'report' || Request::segment(1) == 'reports-monthly-cashflow' || Request::segment(1) == 'reports-quarterly-cashflow' ? 'active dash-trigger ' : '' }}">
                                                <a class="dash-link" href="#">{{ __('Reports') }}<span
                                                        class="dash-arrow"><i
                                                            data-feather="chevron-right"></i></span></a>
                                                <ul class="dash-submenu">
                                                    @can('statement report')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'report.account.statement' ? ' active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.account.statement') }}">{{ __('Account Statement') }}</a>
                                                        </li>
                                                    @endcan
                                                    @can('invoice report')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'report.invoice.summary' ? ' active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.invoice.summary') }}">{{ __('Invoice Summary') }}</a>
                                                        </li>
                                                    @endcan
                                                    <li
                                                        class="dash-item {{ Request::route()->getName() == 'report.sales' ? ' active' : '' }}">
                                                        <a class="dash-link"
                                                            href="{{ route('report.sales') }}">{{ __('Sales Report') }}</a>
                                                    </li>
                                                    <li
                                                        class="dash-item {{ Request::route()->getName() == 'report.receivables' ? ' active' : '' }}">
                                                        <a class="dash-link"
                                                            href="{{ route('report.receivables') }}">{{ __('Receivables') }}</a>
                                                    </li>
                                                    <li
                                                        class="dash-item {{ Request::route()->getName() == 'report.payables' ? ' active' : '' }}">
                                                        <a class="dash-link"
                                                            href="{{ route('report.payables') }}">{{ __('Payables') }}</a>
                                                    </li>
                                                    @can('bill report')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'report.bill.summary' ? ' active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.bill.summary') }}">{{ __('Bill Summary') }}</a>
                                                        </li>
                                                    @endcan
                                                    @can('stock report')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'report.product.stock.report' ? ' active' : '' }}">
                                                            <a href="{{ route('report.product.stock.report') }}"
                                                                class="dash-link">{{ __('Product Stock') }}</a>
                                                        </li>
                                                    @endcan

                                                    @can('loss & profit report')
                                                        <li
                                                            class="dash-item {{ request()->is('reports-monthly-cashflow') || request()->is('reports-quarterly-cashflow') ? 'active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.monthly.cashflow') }}">{{ __('Cash Flow') }}</a>
                                                        </li>
                                                    @endcan
                                                    @can('manage transaction')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'transaction.index' || Request::route()->getName() == 'transfer.create' || Request::route()->getName() == 'transaction.edit' ? ' active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('transaction.index') }}">{{ __('Transaction') }}</a>
                                                        </li>
                                                    @endcan
                                                    @can('income report')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'report.income.summary' ? ' active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.income.summary') }}">{{ __('Income Summary') }}</a>
                                                        </li>
                                                    @endcan
                                                    @can('expense report')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'report.expense.summary' ? ' active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.expense.summary') }}">{{ __('Expense Summary') }}</a>
                                                        </li>
                                                    @endcan
                                                    @can('income vs expense report')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'report.income.vs.expense.summary' ? ' active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.income.vs.expense.summary') }}">{{ __('Income VS Expense') }}</a>
                                                        </li>
                                                    @endcan
                                                    @can('tax report')
                                                        <li
                                                            class="dash-item {{ Request::route()->getName() == 'report.tax.summary' ? ' active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.tax.summary') }}">{{ __('Tax Summary') }}</a>
                                                        </li>
                                                    @endcan
                                                </ul>
                                            </li>
                                        @endif
                                    </ul>
                                </li>
                            @endif

                            @if (!empty($userPlan) && $userPlan->hrm == 1 && \Auth::user()->type != 'accountant' && !session('accountant_impersonating'))
                                @can('show hrm dashboard')
                                    <li
                                        class="dash-item dash-hasmenu {{ Request::segment(1) == 'hrm-dashboard' || Request::segment(1) == 'reports-payroll' || Request::segment(1) == 'report-leave' || Request::segment(1) == 'reports-monthly-attendance' ? ' active dash-trigger' : '' }}">
                                        <a class="dash-link" href="#">{{ __('HRM ') }}<span class="dash-arrow"><i
                                                    data-feather="chevron-right"></i></span></a>
                                        <ul class="dash-submenu">
                                            <li
                                                class="dash-item {{ \Request::route()->getName() == 'hrm.dashboard' ? ' active' : '' }}">
                                                <a class="dash-link"
                                                    href="{{ route('hrm.dashboard') }}">{{ __(' Overview') }}</a>
                                            </li>
                                            @can('manage report')
                                                <li class="dash-item dash-hasmenu
                                                                    {{ Request::segment(1) == 'reports-monthly-attendance' ||
                                                                    Request::segment(1) == 'report-leave' ||
                                                                    Request::segment(1) == 'reports-payroll'
                                                                        ? 'active dash-trigger'
                                                                        : '' }}"
                                                    href="#hr-report" data-toggle="collapse" role="button"
                                                    aria-expanded="{{ Request::segment(1) == 'reports-monthly-attendance' || Request::segment(1) == 'report-leave' || Request::segment(1) == 'reports-payroll' ? 'true' : 'false' }}">
                                                    <a class="dash-link" href="#">{{ __('Reports') }}<span
                                                            class="dash-arrow"><i data-feather="chevron-right"></i></span></a>
                                                    <ul class="dash-submenu">
                                                        <li
                                                            class="dash-item {{ request()->is('reports-payroll') ? 'active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.payroll') }}">{{ __('Payroll') }}</a>
                                                        </li>
                                                        <li
                                                            class="dash-item {{ request()->is('report-leave') ? 'active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.leave') }}">{{ __('Leave') }}</a>
                                                        </li>
                                                        <li
                                                            class="dash-item {{ request()->is('reports-monthly-attendance') ? 'active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.monthly.attendance') }}">{{ __('Monthly Attendance') }}</a>
                                                        </li>
                                                    </ul>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endcan
                            @endif

                            @if (!empty($userPlan) && $userPlan->crm == 1 && !session('accountant_impersonating'))
                                @can('show crm dashboard')
                                    <li
                                        class="dash-item dash-hasmenu {{ Request::segment(1) == 'crm-dashboard' || Request::segment(1) == 'reports-lead' || Request::segment(1) == 'reports-deal' ? ' active dash-trigger' : '' }}">
                                        <a class="dash-link" href="#">{{ __('CRM') }}<span
                                                class="dash-arrow"><i data-feather="chevron-right"></i></span></a>
                                        <ul class="dash-submenu">
                                            <li
                                                class="dash-item {{ \Request::route()->getName() == 'crm.dashboard' ? ' active' : '' }}">
                                                <a class="dash-link"
                                                    href="{{ route('crm.dashboard') }}">{{ __(' Overview') }}</a>
                                            </li>
                                            <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'reports-lead' || Request::segment(1) == 'reports-deal' ? 'active dash-trigger' : '' }}"
                                                href="#crm-report" data-toggle="collapse" role="button"
                                                aria-expanded="{{ Request::segment(1) == 'reports-lead' || Request::segment(1) == 'reports-deal' ? 'true' : 'false' }}">
                                                <a class="dash-link" href="#">{{ __('Reports') }}<span
                                                        class="dash-arrow"><i data-feather="chevron-right"></i></span></a>
                                                <ul class="dash-submenu">
                                                    <li
                                                        class="dash-item {{ request()->is('reports-lead') ? 'active' : '' }}">
                                                        <a class="dash-link"
                                                            href="{{ route('report.lead') }}">{{ __('Lead') }}</a>
                                                    </li>
                                                    <li
                                                        class="dash-item {{ request()->is('reports-deal') ? 'active' : '' }}">
                                                        <a class="dash-link"
                                                            href="{{ route('report.deal') }}">{{ __('Deal') }}</a>
                                                    </li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </li>
                                @endcan
                            @endif

                            @if (!empty($userPlan) && $userPlan->project == 1 && !session('accountant_impersonating'))
                                @can('show project dashboard')
                                    <li
                                        class="dash-item {{ Request::route()->getName() == 'project.dashboard' ? ' active' : '' }}">
                                        <a class="dash-link"
                                            href="{{ route('project.dashboard') }}">{{ __('Project ') }}</a>
                                    </li>
                                @endcan
                            @endif

                            @if (!empty($userPlan) && $userPlan->pos == 1 && \Auth::user()->type != 'accountant' && !session('accountant_impersonating'))
                                @can('show pos dashboard')
                                    <li
                                        class="dash-item dash-hasmenu {{
                                        Request::segment(1) == 'pos-dashboard' ||
                                        Request::segment(1) == 'reports-warehouse' ||
                                        Request::segment(1) == 'reports-daily-purchase' ||
                                        Request::segment(1) == 'reports-monthly-purchase' ||
                                        Request::segment(1) == 'reports-daily-pos' ||
                                        Request::segment(1) == 'reports-monthly-pos' ||
                                        Request::segment(1) == 'reports-pos-vs-purchase' ? ' active dash-trigger' : '' }}">
                                        <a class="dash-link" href="#">{{ __('POS') }}<span
                                                class="dash-arrow"><i data-feather="chevron-right"></i></span></a>
                                        <ul class="dash-submenu">
                                            <li
                                                class="dash-item {{ \Request::route()->getName() == 'pos.dashboard' ? ' active' : '' }}">
                                                <a class="dash-link"
                                                    href="{{ route('pos.dashboard') }}">{{ __(' Overview') }}</a>
                                            </li>
                                            @can('manage pos')
                                                <li class="dash-item dash-hasmenu {{
                                                    Request::segment(1) == 'reports-warehouse' ||
                                                    Request::segment(1) == 'reports-daily-purchase' ||
                                                    Request::segment(1) == 'reports-monthly-purchase' ||
                                                    Request::segment(1) == 'reports-daily-pos' ||
                                                    Request::segment(1) == 'reports-monthly-pos' ||
                                                    Request::segment(1) == 'reports-pos-vs-purchase' ? 'active dash-trigger' : '' }}"
                                                    href="#crm-report" data-toggle="collapse" role="button"
                                                    aria-expanded="{{
                                                    Request::segment(1) == 'reports-warehouse' ||
                                                    Request::segment(1) == 'reports-daily-purchase' ||
                                                    Request::segment(1) == 'reports-monthly-purchase' ||
                                                    Request::segment(1) == 'reports-daily-pos' ||
                                                    Request::segment(1) == 'reports-monthly-pos' ||
                                                    Request::segment(1) == 'reports-pos-vs-purchase' ? 'true' : 'false' }}">
                                                    <a class="dash-link" href="#">{{ __('Reports') }}<span
                                                            class="dash-arrow"><i data-feather="chevron-right"></i></span></a>
                                                    <ul class="dash-submenu">
                                                        <li
                                                            class="dash-item {{ request()->is('reports-warehouse') ? 'active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.warehouse') }}">{{ __('Warehouse Report') }}</a>
                                                        </li>
                                                        <li
                                                            class="dash-item {{ request()->is('reports-daily-purchase') || request()->is('reports-monthly-purchase') ? 'active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.daily.purchase') }}">{{ __('Purchase Daily/Monthly Report') }}</a>
                                                        </li>
                                                        <li
                                                            class="dash-item {{ request()->is('reports-daily-pos') || request()->is('reports-monthly-pos') ? 'active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.daily.pos') }}">{{ __('POS Daily/Monthly Report') }}</a>
                                                        </li>
                                                        <li
                                                            class="dash-item {{ request()->is('reports-pos-vs-purchase') ? 'active' : '' }}">
                                                            <a class="dash-link"
                                                                href="{{ route('report.pos.vs.purchase') }}">{{ __('Pos VS Purchase Report') }}</a>
                                                        </li>
                                                    </ul>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endcan
                            @endif

                        </ul>
                    </li>
                @endif