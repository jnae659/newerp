@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Accountant Invitations') }}
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="card-title">{{ __('Accountant Invitations') }}</h5>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('accountant-invitations.create') }}" class="btn btn-primary">
                                <i class="ti ti-plus"></i> {{ __('Invite Accountant') }}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Accountant') }}</th>
                                    <th>{{ __('Email') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Permissions') }}</th>
                                    <th>{{ __('Sent') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invitations as $invitation)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-3">
                                                    @if($invitation->accountant->avatar)
                                                        <img src="{{ asset($invitation->accountant->avatar) }}" alt="{{ $invitation->accountant->name }}" class="avatar-img rounded-circle">
                                                    @else
                                                        <div class="avatar-initial bg-primary rounded-circle">
                                                            {{ strtoupper(substr($invitation->accountant->name, 0, 1)) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $invitation->accountant->name }}</h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $invitation->email }}</td>
                                        <td>
                                            @if($invitation->isPending())
                                                <span class="badge bg-warning">{{ __('Pending') }}</span>
                                            @elseif($invitation->isAccepted())
                                                <span class="badge bg-success">{{ __('Accepted') }}</span>
                                            @elseif($invitation->isRejected())
                                                <span class="badge bg-danger">{{ __('Rejected') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('Cancelled') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($invitation->permissions)
                                                <small>{{ implode(', ', $invitation->permissions) }}</small>
                                                @if($invitation->isPending() && Auth::user()->type === 'company')
                                                    <br>
                                                    <button type="button" class="btn btn-sm btn-outline-primary mt-1" data-bs-toggle="modal" data-bs-target="#editPermissionsModal{{ $invitation->id }}">
                                                        {{ __('Edit') }}
                                                    </button>
                                                @endif
                                            @else
                                                <small class="text-muted">{{ __('None') }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $invitation->created_at->format('M j, Y') }}</td>
                                        <td>
                                            @if(($invitation->isPending() || $invitation->isAccepted()) && Auth::user()->type === 'company')
                                                <a href="{{ route('accountant-invitations.edit', $invitation->id) }}" class="btn btn-sm btn-outline-primary me-1">
                                                    <i class="ti ti-edit"></i> {{ __('Edit') }}
                                                </a>
                                            @endif

                                            @if($invitation->isPending() && Auth::user()->type === 'company')
                                                <form method="POST" action="{{ route('accountant-invitations.cancel', $invitation->id) }}" class="d-inline">
                                                    @csrf
                                                    @method('POST')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('Are you sure you want to cancel this invitation?') }}')">
                                                        {{ __('Cancel') }}
                                                    </button>
                                                </form>
                                            @elseif($invitation->isAccepted() && Auth::user()->type === 'company')
                                                <form method="POST" action="{{ route('accountant-invitations.remove', $invitation->id) }}" class="d-inline">
                                                    @csrf
                                                    @method('POST')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('Are you sure you want to remove this accountant?') }}')">
                                                        {{ __('Remove') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="ti ti-user-x fs-1 d-block mb-2"></i>
                                                {{ __('No accountant invitations found.') }}
                                                <br>
                                                <a href="{{ route('accountant-invitations.create') }}" class="btn btn-sm btn-primary mt-2">
                                                    {{ __('Send Your First Invitation') }}
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($invitations->hasPages())
                        <div class="d-flex justify-content-center mt-3">
                            {{ $invitations->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Permissions Modals -->
    @foreach($invitations as $invitation)
        @if(($invitation->isPending() || $invitation->isAccepted()) && Auth::user()->type === 'company')
        <div class="modal fade" id="editPermissionsModal{{ $invitation->id }}" tabindex="-1" aria-labelledby="editPermissionsModalLabel{{ $invitation->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editPermissionsModalLabel{{ $invitation->id }}">{{ __('Edit Permissions for') }} {{ $invitation->accountant->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('accountant-invitations.update-permissions', $invitation->id) }}">
                        @csrf
                        @method('POST')
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">{{ __('Access Permissions') }}</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="read" id="perm_read_{{ $invitation->id }}" {{ in_array('read', $invitation->permissions ?? []) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="perm_read_{{ $invitation->id }}">
                                                {{ __('Read Access') }}
                                            </label>
                                            <small class="form-text text-muted">{{ __('View financial data and reports') }}</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="write" id="perm_write_{{ $invitation->id }}" {{ in_array('write', $invitation->permissions ?? []) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="perm_write_{{ $invitation->id }}">
                                                {{ __('Write Access') }}
                                            </label>
                                            <small class="form-text text-muted">{{ __('Create and modify financial records') }}</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="invoice" id="perm_invoice_{{ $invitation->id }}" {{ in_array('invoice', $invitation->permissions ?? []) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="perm_invoice_{{ $invitation->id }}">
                                                {{ __('Invoice Management') }}
                                            </label>
                                            <small class="form-text text-muted">{{ __('Create and manage invoices') }}</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="reports" id="perm_reports_{{ $invitation->id }}" {{ in_array('reports', $invitation->permissions ?? []) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="perm_reports_{{ $invitation->id }}">
                                                {{ __('Financial Reports') }}
                                            </label>
                                            <small class="form-text text-muted">{{ __('Access to all financial reports') }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('Update Permissions') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    @endforeach
@endsection

<style>
.avatar-initial {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: bold;
    color: white;
}
</style>
