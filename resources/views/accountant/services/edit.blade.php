@extends('layouts.admin')
@section('page-title')
    {{__('Edit Service')}}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item"><a href="{{route('accountant.services.index')}}">{{__('My Services')}}</a></li>
    <li class="breadcrumb-item">{{__('Edit Service')}}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{__('Edit Service')}}</h5>
                    <small class="text-muted">{{__('Update your accounting service information')}}</small>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('accountant.services.update', $service->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="service_name" class="form-label">{{__('Service Name')}} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('service_name') is-invalid @enderror"
                                           id="service_name" name="service_name"
                                           value="{{ old('service_name', $service->service_name) }}"
                                           placeholder="{{__('e.g. Tax Preparation, Bookkeeping, Financial Planning')}}"
                                           required>
                                    @error('service_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category" class="form-label">{{__('Category')}}</label>
                                    <select class="form-control @error('category') is-invalid @enderror"
                                            id="category" name="category">
                                        <option value="">{{__('Select Category')}}</option>
                                        <option value="Tax Services" {{ old('category', $service->category) == 'Tax Services' ? 'selected' : '' }}>{{__('Tax Services')}}</option>
                                        <option value="Bookkeeping" {{ old('category', $service->category) == 'Bookkeeping' ? 'selected' : '' }}>{{__('Bookkeeping')}}</option>
                                        <option value="Financial Planning" {{ old('category', $service->category) == 'Financial Planning' ? 'selected' : '' }}>{{__('Financial Planning')}}</option>
                                        <option value="Audit Services" {{ old('category', $service->category) == 'Audit Services' ? 'selected' : '' }}>{{__('Audit Services')}}</option>
                                        <option value="Payroll Services" {{ old('category', $service->category) == 'Payroll Services' ? 'selected' : '' }}>{{__('Payroll Services')}}</option>
                                        <option value="Business Consulting" {{ old('category', $service->category) == 'Business Consulting' ? 'selected' : '' }}>{{__('Business Consulting')}}</option>
                                        <option value="Other" {{ old('category', $service->category) == 'Other' ? 'selected' : '' }}>{{__('Other')}}</option>
                                    </select>
                                    @error('category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">{{__('Description')}}</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="4"
                                      placeholder="{{__('Describe your service, what it includes, and what clients can expect')}}">{{ old('description', $service->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="hourly_rate" class="form-label">{{__('Hourly Rate')}} ({{ \Auth::user()->currency }})</label>
                                    <input type="number" class="form-control @error('hourly_rate') is-invalid @enderror"
                                           id="hourly_rate" name="hourly_rate" step="0.01" min="0"
                                           value="{{ old('hourly_rate', $service->hourly_rate) }}"
                                           placeholder="0.00">
                                    @error('hourly_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="monthly_rate" class="form-label">{{__('Monthly Rate')}} ({{ \Auth::user()->currency }})</label>
                                    <input type="number" class="form-control @error('monthly_rate') is-invalid @enderror"
                                           id="monthly_rate" name="monthly_rate" step="0.01" min="0"
                                           value="{{ old('monthly_rate', $service->monthly_rate) }}"
                                           placeholder="0.00">
                                    @error('monthly_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fixed_rate" class="form-label">{{__('Fixed Rate')}} ({{ \Auth::user()->currency }})</label>
                                    <input type="number" class="form-control @error('fixed_rate') is-invalid @enderror"
                                           id="fixed_rate" name="fixed_rate" step="0.01" min="0"
                                           value="{{ old('fixed_rate', $service->fixed_rate) }}"
                                           placeholder="0.00">
                                    @error('fixed_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>



                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_available" name="is_available" value="1"
                                   {{ old('is_available', $service->is_available) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_available">
                                {{__('Make this service available to clients')}}
                            </label>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">{{__('Update Service')}}</button>
                            <a href="{{ route('accountant.services.index') }}" class="btn btn-secondary ms-2">{{__('Cancel')}}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<script>
    // Form validation
    $(document).ready(function() {
        $('form').on('submit', function(e) {
            var hasRate = false;
            $('input[name="hourly_rate"], input[name="monthly_rate"], input[name="fixed_rate"]').each(function() {
                if ($(this).val() && parseFloat($(this).val()) > 0) {
                    hasRate = true;
                }
            });

            if (!hasRate) {
                e.preventDefault();
                alert('{{__("Please set at least one pricing option (hourly, monthly, or fixed rate)")}}');
                return false;
            }
        });
    });
</script>
@endpush
