@extends('layouts.admin')
@section('page-title')
    {{__('My Services')}}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('My Services')}}</li>
@endsection

@section('action-button')
    <div class="float-end">
        <a href="{{ route('accountant.services.create') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i> {{__('Add Service')}}
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">{{__('My Services')}}</h5>
                        <small class="text-muted">{{__('Manage your accounting services and pricing')}}</small>
                    </div>
                    <a href="{{ route('accountant.services.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus"></i> {{__('Add New Service')}}
                    </a>
                </div>
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{__('Service Name')}}</th>
                                    <th>{{__('Category')}}</th>
                                    <th>{{__('Pricing')}}</th>
                                    <th>{{__('Status')}}</th>
                                    <th>{{__('Created')}}</th>
                                    <th class="text-end">{{__('Action')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($services as $service)
                                    <tr>
                                        <td>
                                            <div class="font-weight-bold">{{ $service->service_name }}</div>
                                            @if($service->description)
                                                <small class="text-muted">{{ Str::limit($service->description, 50) }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $service->category ?: '-' }}</td>
                                        <td>
                                            @if($service->hourly_rate)
                                                <div>{{__('Hourly')}}: {{ \Auth::user()->priceFormat($service->hourly_rate) }}</div>
                                            @endif
                                            @if($service->monthly_rate)
                                                <div>{{__('Monthly')}}: {{ \Auth::user()->priceFormat($service->monthly_rate) }}</div>
                                            @endif
                                            @if($service->fixed_rate)
                                                <div>{{__('Fixed')}}: {{ \Auth::user()->priceFormat($service->fixed_rate) }}</div>
                                            @endif
                                            @if(!$service->hourly_rate && !$service->monthly_rate && !$service->fixed_rate)
                                                <span class="text-muted">{{__('Not set')}}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($service->is_available)
                                                <span class="badge bg-success">{{__('Available')}}</span>
                                            @else
                                                <span class="badge bg-warning">{{__('Unavailable')}}</span>
                                            @endif
                                        </td>
                                        <td>{{ \Auth::user()->dateFormat($service->created_at) }}</td>
                                        <td class="text-end">
                                            <div class="action-btn bg-warning ms-2">
                                                <a href="{{ route('accountant.services.edit', $service->id) }}" class="mx-3 btn btn-sm d-inline-flex align-items-center" data-bs-toggle="tooltip" title="{{__('Edit')}}">
                                                    <i class="ti ti-pencil text-white"></i>
                                                </a>
                                            </div>
                                            <div class="action-btn bg-danger ms-2">
                                                <form method="POST" action="{{ route('accountant.services.destroy', $service->id) }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="mx-3 btn btn-sm d-inline-flex align-items-center show_confirm" data-bs-toggle="tooltip" title="{{__('Delete')}}">
                                                        <i class="ti ti-trash text-white"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="py-4">
                                                <i class="ti ti-package-off fs-2 text-muted d-block mb-2"></i>
                                                <h6 class="text-muted">{{__('No services found')}}</h6>
                                                <p class="text-muted mb-3">{{__('Start by adding your first accounting service to attract clients.')}}</p>
                                                <a href="{{ route('accountant.services.create') }}" class="btn btn-primary">
                                                    <i class="ti ti-plus"></i> {{__('Add Your First Service')}}
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($services->hasPages())
                    <div class="card-footer">
                        {{ $services->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<script>
    $(document).on('click', '.show_confirm', function (e) {
        e.preventDefault();
        var form = $(this).closest('form');
        Swal.fire({
            title: '{{__("Are You Sure?")}}',
            text: '{{__("This action cannot be undone. Do you want to continue?")}}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '{{__("Yes, delete it!")}}',
            cancelButtonText: '{{__("Cancel")}}'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
</script>
@endpush
