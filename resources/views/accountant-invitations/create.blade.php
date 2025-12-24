@extends('layouts.admin')
@section('page-title')
    {{ __('Invite Accountant') }}
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ __('Send Invitation to Accountant') }}</h5>
                    <p class="card-text">{{ __('Invite a certified accountant to work with your company') }}</p>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('accountant-invitations.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('Accountant Email') }}</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="accountant@example.com" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">{{ __('Enter the email address of a registered accountant') }}</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Access Permissions') }}</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="read" id="perm_read" checked>
                                        <label class="form-check-label" for="perm_read">
                                            {{ __('Read Access') }}
                                        </label>
                                        <small class="form-text text-muted">{{ __('View financial data and reports') }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="write" id="perm_write">
                                        <label class="form-check-label" for="perm_write">
                                            {{ __('Write Access') }}
                                        </label>
                                        <small class="form-text text-muted">{{ __('Create and modify financial records') }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="invoice" id="perm_invoice">
                                        <label class="form-check-label" for="perm_invoice">
                                            {{ __('Invoice Management') }}
                                        </label>
                                        <small class="form-text text-muted">{{ __('Create and manage invoices') }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="reports" id="perm_reports">
                                        <label class="form-check-label" for="perm_reports">
                                            {{ __('Financial Reports') }}
                                        </label>
                                        <small class="form-text text-muted">{{ __('Access to all financial reports') }}</small>
                                    </div>
                                </div>
                            </div>
                            @error('permissions')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">{{ __('Personal Message (Optional)') }}</label>
                            <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="4" placeholder="Introduce yourself and explain what you need help with...">{{ old('message') }}</textarea>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-send"></i> {{ __('Send Invitation') }}
                            </button>
                            <a href="{{ route('accountant-invitations.index') }}" class="btn btn-outline-secondary">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title">{{ __('How It Works') }}</h6>
                </div>
                <div class="card-body">
                    <div class="step mb-3">
                        <div class="d-flex align-items-start">
                            <div class="step-number bg-primary text-white rounded-circle me-3" style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;">1</div>
                            <div>
                                <h6 class="mb-1">{{ __('Enter Accountant Email') }}</h6>
                                <p class="text-muted small mb-0">{{ __('Make sure the accountant is registered on our platform') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="step mb-3">
                        <div class="d-flex align-items-start">
                            <div class="step-number bg-primary text-white rounded-circle me-3" style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;">2</div>
                            <div>
                                <h6 class="mb-1">{{ __('Set Permissions') }}</h6>
                                <p class="text-muted small mb-0">{{ __('Choose what the accountant can access in your account') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="step mb-3">
                        <div class="d-flex align-items-start">
                            <div class="step-number bg-primary text-white rounded-circle me-3" style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;">3</div>
                            <div>
                                <h6 class="mb-1">{{ __('Accountant Accepts') }}</h6>
                                <p class="text-muted small mb-0">{{ __('The accountant will receive your invitation and can accept or decline') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="step">
                        <div class="d-flex align-items-start">
                            <div class="step-number bg-success text-white rounded-circle me-3" style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;">4</div>
                            <div>
                                <h6 class="mb-1">{{ __('Start Working Together') }}</h6>
                                <p class="text-muted small mb-0">{{ __('Once accepted, the accountant can access your data according to the permissions granted') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title">{{ __('Need Help Finding an Accountant?') }}</h6>
                </div>
                <div class="card-body text-center">
                    <p class="text-muted">{{ __('Browse our marketplace of certified accountants') }}</p>
                    <a href="{{ route('accountant.marketplace') }}" class="btn btn-outline-primary btn-sm">
                        <i class="ti ti-search"></i> {{ __('Browse Marketplace') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-check read permission when any permission is selected
    const permissionCheckboxes = document.querySelectorAll('input[name="permissions[]"]');
    const readCheckbox = document.getElementById('perm_read');

    permissionCheckboxes.forEach(checkbox => {
        if (checkbox !== readCheckbox) {
            checkbox.addEventListener('change', function() {
                if (this.checked && !readCheckbox.checked) {
                    readCheckbox.checked = true;
                }
            });
        }
    });
});
</script>
