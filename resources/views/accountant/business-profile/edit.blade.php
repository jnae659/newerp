@extends('layouts.admin')
@section('page-title')
    {{__('Business Profile')}}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Business Profile')}}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{__('Edit Business Profile')}}</h5>
                    <small class="text-muted">{{__('Update your business profile information that will be displayed to potential clients')}}</small>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('accountant.business-profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="form-label">{{__('Full Name')}} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name"
                                           value="{{ old('name', $user->name) }}"
                                           placeholder="{{__('Enter your full name')}}"
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email" class="form-label">{{__('Email Address')}} <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                           id="email" name="email"
                                           value="{{ old('email', $user->email) }}"
                                           placeholder="{{__('Enter your email address')}}"
                                           required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">{{__('Accountant Type')}} <span class="text-danger">*</span></label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input @error('accountant_type') is-invalid @enderror"
                                           type="radio" id="accountant_type_individual"
                                           name="accountant_type" value="individual"
                                           {{ old('accountant_type', $user->accountant_type ?? 'individual') == 'individual' ? 'checked' : '' }}
                                           required>
                                    <label class="form-check-label" for="accountant_type_individual">
                                        {{__('Individual Accountant')}}
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input @error('accountant_type') is-invalid @enderror"
                                           type="radio" id="accountant_type_firm"
                                           name="accountant_type" value="firm"
                                           {{ old('accountant_type', $user->accountant_type ?? 'individual') == 'firm' ? 'checked' : '' }}
                                           required>
                                    <label class="form-check-label" for="accountant_type_firm">
                                        {{__('Accountant Firm')}}
                                    </label>
                                </div>
                            </div>
                            @error('accountant_type')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="avatar" class="form-label">{{__('Profile Picture')}}</label>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    @if($user->avatar)
                                        <img src="{{ asset($user->avatar) }}" alt="Current Avatar" class="rounded-circle" width="80" height="80">
                                    @else
                                        <img src="{{ asset('assets/img/avatar/avatar.png') }}" alt="Default Avatar" class="rounded-circle" width="80" height="80">
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" class="form-control @error('avatar') is-invalid @enderror"
                                           id="avatar" name="avatar" accept="image/*">
                                    <small class="text-muted">{{__('Upload a professional profile picture. Maximum file size: 2MB. Supported formats: JPG, PNG, GIF.')}}</small>
                                    @error('avatar')
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
                                           value="{{ old('experience_years', $user->experience_years) }}"
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
                                           value="{{ old('certifications', $user->certifications) }}"
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
                                           value="{{ old('education', $user->education) }}"
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
                                            $selectedLanguages = old('languages', $user->languages ?? []);
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
                            <label for="bio" class="form-label">{{__('Professional Bio')}} <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('bio') is-invalid @enderror"
                                      id="bio" name="bio" rows="4"
                                      placeholder="{{__('Tell clients about yourself, your experience, and what makes you unique')}}"
                                      required>{{ old('bio', $user->bio) }}</textarea>
                            @error('bio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">{{__('This information will be displayed to potential clients in the marketplace.')}}</small>
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
                                    $selectedSpecialties = old('specialties', $user->specialties ?? []);
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

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">{{__('Update Profile')}}</button>
                            <a href="{{ route('dashboard') }}" class="btn btn-secondary ms-2">{{__('Cancel')}}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<script>
    // Avatar preview
    $(document).ready(function() {
        $('#avatar').change(function() {
            var input = this;
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('.rounded-circle').attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        });
    });
</script>
@endpush
