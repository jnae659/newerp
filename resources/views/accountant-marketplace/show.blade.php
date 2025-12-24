@extends('layouts.admin')
@section('page-title')
    {{ __('Accountant Profile') }} - {{ $accountant->name }}
@endsection

@section('content')
    <div class="row">
        <!-- Accountant Profile -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                                        <div class="avatar avatar-xl mb-3">
                                            @if($accountant->avatar)
                                                <img src="{{ $accountant->profile }}" alt="{{ $accountant->name }}" class="avatar-img rounded-circle">
                                            @else
                                                <div class="avatar-initial bg-primary rounded-circle">
                                                    {{ strtoupper(substr($accountant->name, 0, 1)) }}
                                                </div>
                                            @endif
                                        </div>
                        </div>
                        <div class="col-md-8">
                            <h3 class="card-title">{{ $accountant->name }}</h3>
                            <div class="mb-2">
                                @if($accountant->accountant_type === 'firm')
                                    <span class="badge bg-primary">{{ __('Accountant Firm') }}</span>
                                @else
                                    <span class="badge bg-info">{{ __('Individual Accountant') }}</span>
                                @endif
                            </div>
                            <p class="text-muted mb-2">{{ $accountant->experience_years }} years of experience</p>

                            <div class="mb-3">
                                <h6>{{ __('Specialties') }}</h6>
                                <div>
                                    @foreach($accountant->specialties as $specialty)
                                        <span class="badge bg-light text-dark me-1">{{ $specialty }}</span>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mb-3">
                                <h6>{{ __('Certifications') }}</h6>
                                <div>
                                    @foreach($accountant->certifications as $certification)
                                        <span class="badge bg-success me-1">{{ $certification }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <h6>{{ __('About') }}</h6>
                            <p>{{ $accountant->description }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>{{ __('Education') }}</h6>
                            <p>{{ $accountant->education }}</p>

                            <h6>{{ __('Languages') }}</h6>
                            <p>{{ implode(', ', $accountant->languages) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Services Section -->
    @if($services->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ __('Services Offered') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($services as $service)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">{{ $service->service_name }}</h6>
                                    @if($service->description)
                                        <p class="card-text text-muted">{{ Str::limit($service->description, 100) }}</p>
                                    @endif

                                    <div class="mb-3">
                                        <strong>{{ __('Pricing') }}:</strong>
                                        <ul class="list-unstyled">
                                            @if($service->hourly_rate)
                                                <li>{{__('Hourly')}}: {{ \Auth::user()->priceFormat($service->hourly_rate) }}</li>
                                            @endif
                                            @if($service->monthly_rate)
                                                <li>{{__('Monthly')}}: {{ \Auth::user()->priceFormat($service->monthly_rate) }}</li>
                                            @endif
                                            @if($service->fixed_rate)
                                                <li>{{__('Fixed')}}: {{ \Auth::user()->priceFormat($service->fixed_rate) }}</li>
                                            @endif
                                        </ul>
                                    </div>

                                    <form method="POST" action="{{ route('accountant.marketplace.request.service', [$accountant->id, $service->id]) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            {{ __('Send Service Request') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Contact Information -->
    <div class="row mt-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title">{{ __('Contact Information') }}</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>{{ __('Email') }}:</strong> {{ $accountant->email }}
                    </div>
                    <div class="mb-2">
                        <strong>{{ __('Member Since') }}:</strong> {{ $accountant->created_at->format('M Y') }}
                    </div>
                    <div class="mb-2">
                        <strong>{{ __('Response Time') }}:</strong> Within 24 hours
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

<style>
.avatar-initial {
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: bold;
    color: white;
}
</style>
