@if (\Auth::user()->type != 'super admin' && !session('accountant_impersonating'))
            <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'support' ? 'active' : '' }}">
                <a href="{{ route('support.index') }}" class="dash-link">
                    <span class="dash-micon"><i class="ti ti-headphones"></i></span><span
                        class="dash-mtext">{{ __('Support System') }}</span>
                </a>
            </li>
            <li
                class="dash-item dash-hasmenu {{ Request::segment(1) == 'zoom-meeting' || Request::segment(1) == 'zoom-meeting-calender' ? 'active' : '' }}">
                <a href="{{ route('zoom-meeting.index') }}" class="dash-link">
                    <span class="dash-micon"><i class="ti ti-user-check"></i></span><span
                        class="dash-mtext">{{ __('Zoom Meeting') }}</span>
                </a>
            </li>
            <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'chats' ? 'active' : '' }}">
                <a href="{{ url('chats') }}" class="dash-link">
                    <span class="dash-micon"><i class="ti ti-message-circle"></i></span><span
                        class="dash-mtext">{{ __('Messenger') }}</span>
                </a>
            </li>
        @endif

        @if (\Auth::user()->type == 'company' && !session('accountant_impersonating'))
            <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'notification_templates' ? 'active' : '' }}">
                <a href="{{ route('notification-templates.index') }}" class="dash-link">
                    <span class="dash-micon"><i class="ti ti-notification"></i></span><span
                        class="dash-mtext">{{ __('Notification Template') }}</span>
                </a>
            </li>
            <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'accountant-marketplace' ? 'active' : '' }}">
                <a href="{{ route('accountant.marketplace') }}" class="dash-link">
                    <span class="dash-micon"><i class="ti ti-building-store"></i></span><span
                        class="dash-mtext">{{ __('Accountant Marketplace') }}</span>
                </a>
            </li>
            <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'accountant-invitations' ? 'active' : '' }}">
                <a href="{{ route('accountant-invitations.index') }}" class="dash-link">
                    <span class="dash-micon"><i class="ti ti-users"></i></span><span
                        class="dash-mtext">{{ __('Manage Accountants') }}</span>
                </a>
            </li>
        @endif
