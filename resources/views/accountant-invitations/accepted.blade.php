@extends('layouts.admin')
@section('page-title')
    {{ __('My Clients') }}
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ __('My Clients') }}</h5>
                    <p class="card-text">{{ __('Companies you have been accepted to work with') }}</p>
                </div>
                <div class="card-body">
                    @forelse($invitations as $invitation)
                        <div class="invitation-card mb-4 p-4 border rounded">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-start">
                                        <div class="avatar avatar-lg me-3">
                                            @if($invitation->company->avatar)
                                                <img src="{{ asset($invitation->company->avatar) }}" alt="{{ $invitation->company->name }}" class="avatar-img rounded-circle">
                                            @else
                                                <div class="avatar-initial bg-primary rounded-circle">
                                                    {{ strtoupper(substr($invitation->company->name, 0, 1)) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1">{{ $invitation->company->name }}</h5>
                                            <p class="text-muted mb-2">{{ $invitation->company->email }}</p>

                                            @if($invitation->message)
                                                <div class="invitation-message mb-3">
                                                    <strong>{{ __('Message:') }}</strong>
                                                    <p class="mb-0">{{ $invitation->message }}</p>
                                                </div>
                                            @endif

                                            <div class="permissions mb-3">
                                                <strong>{{ __('Permissions:') }}</strong>
                                                @if($invitation->permissions)
                                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                                        @foreach($invitation->permissions as $permission)
                                                            <span class="badge bg-light text-dark">{{ ucfirst($permission) }}</span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-muted">{{ __('No specific permissions set') }}</span>
                                                @endif
                                            </div>

                                            <small class="text-muted">
                                                {{ __('Accepted on') }} {{ $invitation->accepted_at->format('M j, Y \a\t g:i A') }}
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 text-end">
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('login.with.company', $invitation->company_id) }}" class="btn btn-success btn-sm">
                                            <i class="ti ti-login"></i> {{ __('Login to Company') }}
                                        </a>
                                        <small class="text-muted">{{ __('Access company accounting') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <div class="text-muted">
                                <i class="ti ti-building-x fs-1 d-block mb-3"></i>
                                <h5>{{ __('No Clients') }}</h5>
                                <p>{{ __('You haven\'t been accepted to work with any companies yet.') }}</p>
                                <a href="{{ route('accountant.marketplace') }}" class="btn btn-primary">
                                    <i class="ti ti-search"></i> {{ __('Browse Companies') }}
                                </a>
                            </div>
                        </div>
                    @endforelse

                    <!-- Pagination -->
                    @if($invitations->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $invitations->links() }}
                        </div>
                    @endif
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

<style>
.invitation-card {
    background: #f8f9fa;
    border-left: 4px solid #28a745 !important;
}

.invitation-card:hover {
    background: #f1f3f4;
    transition: background-color 0.2s;
}

.invitation-message {
    background: white;
    padding: 12px;
    border-radius: 6px;
    border-left: 3px solid #28a745;
}

.avatar-initial {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
    color: white;
}

.permissions .badge {
    font-size: 0.75rem;
    padding: 4px 8px;
}
</style>
