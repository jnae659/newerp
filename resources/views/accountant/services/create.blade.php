@extends('layouts.admin')
@section('page-title')
    {{__('Add Service')}}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item"><a href="{{route('accountant.services.index')}}">{{__('My Services')}}</a></li>
    <li class="breadcrumb-item">{{__('Add Service')}}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{__('Add New Service')}}</h5>
                    <small class="text-muted">{{__('Create a new accounting service to offer to clients')}}</small>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('accountant.services.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="service_name" class="form-label">{{__('Service Name')}} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('service_name') is-invalid @enderror"
                                           id="service_name" name="service_name"
                                           value="{{ old('service_name') }}"
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
                                        <option value="Tax Services" {{ old('category') == 'Tax Services' ? 'selected' : '' }}>{{__('Tax Services')}}</option>
                                        <option value="Bookkeeping" {{ old('category') == 'Bookkeeping' ? 'selected' : '' }}>{{__('Bookkeeping')}}</option>
                                        <option value="Financial Planning" {{ old('category') == 'Financial Planning' ? 'selected' : '' }}>{{__('Financial Planning')}}</option>
                                        <option value="Audit Services" {{ old('category') == 'Audit Services' ? 'selected' : '' }}>{{__('Audit Services')}}</option>
                                        <option value="Payroll Services" {{ old('category') == 'Payroll Services' ? 'selected' : '' }}>{{__('Payroll Services')}}</option>
                                        <option value="Business Consulting" {{ old('category') == 'Business Consulting' ? 'selected' : '' }}>{{__('Business Consulting')}}</option>
                                        <option value="Other" {{ old('category') == 'Other' ? 'selected' : '' }}>{{__('Other')}}</option>
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
                                      placeholder="{{__('Describe your service, what it includes, and what clients can expect')}}">{{ old('description') }}</textarea>
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
                                           value="{{ old('hourly_rate') }}"
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
                                           value="{{ old('monthly_rate') }}"
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
                                           value="{{ old('fixed_rate') }}"
                                           placeholder="0.00">
                                    @error('fixed_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="experience_years" class="form-label">{{__('Years of Experience')}}</label>
                                    <input type="number" class="form-control @error('experience_years') is-invalid @enderror"
                                           id="experience_years" name="experience_years" min="0"
                                           value="{{ old('experience_years') }}"
                                           placeholder="{{__('e.g. 5')}}">
                                    @error('experience_years')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="certifications" class="form-label">{{__('Certifications')}}</label>
                                    <input type="text" class="form-control @error('certifications') is-invalid @enderror"
                                           id="certifications" name="certifications"
                                           value="{{ old('certifications') }}"
                                           placeholder="{{__('e.g. CPA, CMA, CFE')}}">
                                    @error('certifications')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="education" class="form-label">{{__('Education')}}</label>
                                    <input type="text" class="form-control @error('education') is-invalid @enderror"
                                           id="education" name="education"
                                           value="{{ old('education') }}"
                                           placeholder="{{__('e.g. MBA in Finance')}}">
                                    @error('education')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{__('Languages Spoken')}}</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        @php
                                            $languages = ['English', 'Spanish', 'French', 'German', 'Chinese', 'Japanese', 'Arabic', 'Hindi', 'Portuguese', 'Russian'];
                                            $selectedLanguages = old('languages', []);
                                        @endphp
                                        @foreach($languages as $language)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                       id="lang_{{ $language }}" name="languages[]"
                                                       value="{{ $language }}"
                                                       {{ in_array($language, $selectedLanguages) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="lang_{{ $language }}">
                                                    {{ $language }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('languages')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="bio" class="form-label">{{__('Professional Bio')}}</label>
                            <textarea class="form-control @error('bio') is-invalid @enderror"
                                      id="bio" name="bio" rows="3"
                                      placeholder="{{__('Tell clients about yourself, your experience, and what makes you unique')}}">{{ old('bio') }}</textarea>
                            @error('bio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">{{__('Specialties')}}</label>
                            <div class="d-flex flex-wrap gap-2">
                                @php
                                    $specialties = [
                                        'Tax Planning', 'Tax Preparation', 'Bookkeeping', 'Financial Reporting',
                                        'Audit Services', 'Payroll Management', 'Financial Planning', 'Business Consulting',
                                        'QuickBooks Setup', 'Xero Setup', 'Budgeting', 'Cash Flow Management',
                                        'Business Valuation', 'Estate Planning', 'Retirement Planning'
                                    ];
                                    $selectedSpecialties = old('specialties', []);
                                @endphp
                                @foreach($specialties as $specialty)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               id="spec_{{ str_replace(' ', '_', $specialty) }}" name="specialties[]"
                                               value="{{ $specialty }}"
                                               {{ in_array($specialty, $selectedSpecialties) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="spec_{{ str_replace(' ', '_', $specialty) }}">
                                            {{ $specialty }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('specialties')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_available" name="is_available" value="1" checked>
                            <label class="form-check-label" for="is_available">
                                {{__('Make this service available to clients')}}
                            </label>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">{{__('Create Service')}}</button>
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
