@extends('layouts.admin')
@section('page-title')
    {{ __('Edit Accountant Invitation') }}
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ __('Edit Invitation for') }} {{ $invitation->accountant->name }}</h5>
                    <p class="card-text">{{ __('Modify the permissions for this invitation') }}</p>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('accountant-invitations.update', $invitation->id) }}">
                        @csrf
                        @method('PUT')



                        <div class="mb-3">
                            <label class="form-label">{{ __('Access Permissions') }}</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="read" id="perm_read" {{ in_array('read', old('permissions', $invitation->permissions ?? [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="perm_read">
                                            {{ __('Read Access') }}
                                        </label>
                                        <small class="form-text text-muted">{{ __('View financial data and reports') }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="write" id="perm_write" {{ in_array('write', old('permissions', $invitation->permissions ?? [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="perm_write">
                                            {{ __('Write Access') }}
                                        </label>
                                        <small class="form-text text-muted">{{ __('Create and modify financial records') }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="invoice" id="perm_invoice" {{ in_array('invoice', old('permissions', $invitation->permissions ?? [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="perm_invoice">
                                            {{ __('Invoice Management') }}
                                        </label>
                                        <small class="form-text text-muted">{{ __('Create and manage invoices') }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="reports" id="perm_reports" {{ in_array('reports', old('permissions', $invitation->permissions ?? [])) ? 'checked' : '' }}>
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

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy"></i> {{ __('Update Invitation') }}
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
                    <h6 class="card-title">{{ __('Invitation Details') }}</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>{{ __('Accountant') }}:</strong> {{ $invitation->accountant->name }}
                    </div>
                    <div class="mb-2">
                        <strong>{{ __('Email') }}:</strong> {{ $invitation->email }}
                    </div>
                    <div class="mb-2">
                        <strong>{{ __('Status') }}:</strong>
                        @if($invitation->isPending())
                            <span class="badge bg-warning">{{ __('Pending') }}</span>
                        @elseif($invitation->isAccepted())
                            <span class="badge bg-success">{{ __('Accepted') }}</span>
                        @elseif($invitation->isRejected())
                            <span class="badge bg-danger">{{ __('Rejected') }}</span>
                        @else
                            <span class="badge bg-secondary">{{ __('Cancelled') }}</span>
                        @endif
                    </div>
                    <div class="mb-2">
                        <strong>{{ __('Sent') }}:</strong> {{ $invitation->created_at->format('M j, Y \a\t g:i A') }}
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title">{{ __('Permission Explanations') }}</h6>
                </div>
                <div class="card-body">
                    <div class="permission-info mb-3">
                        <h6 class="text-primary">{{ __('Read Access') }}</h6>
                        <p class="small text-muted">{{ __('Allows viewing financial data, reports, and basic information') }}</p>
                    </div>
                    <div class="permission-info mb-3">
                        <h6 class="text-primary">{{ __('Write Access') }}</h6>
                        <p class="small text-muted">{{ __('Allows creating and modifying financial records, transactions, and entries') }}</p>
                    </div>
                    <div class="permission-info mb-3">
                        <h6 class="text-primary">{{ __('Invoice Management') }}</h6>
                        <p class="small text-muted">{{ __('Allows creating, editing, and managing invoices and billing') }}</p>
                    </div>
                    <div class="permission-info mb-3">
                        <h6 class="text-primary">{{ __('Financial Reports') }}</h6>
                        <p class="small text-muted">{{ __('Allows access to all financial reports and analytics') }}</p>
                    </div>
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
