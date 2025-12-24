@extends('layouts.admin')
@section('page-title')
    {{ __('Accountant Marketplace') }}
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ __('Find Professional Accountants') }}</h5>
                    <p class="card-text">{{ __('Browse certified accountants available for hire') }}</p>
                </div>
                <div class="card-body">
                    <!-- Search and Filters -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <input type="text" class="form-control" placeholder="{{ __('Search accountants...') }}" id="searchInput">
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" id="specialtyFilter">
                                <option value="">{{ __('All Specialties') }}</option>
                                <option value="Tax Planning">{{ __('Tax Planning') }}</option>
                                <option value="Bookkeeping">{{ __('Bookkeeping') }}</option>
                                <option value="Financial Reporting">{{ __('Financial Reporting') }}</option>
                                <option value="Audit Preparation">{{ __('Audit Preparation') }}</option>
                                <option value="Payroll Management">{{ __('Payroll Management') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" id="sortBy">
                                <option value="rating">{{ __('Sort by Rating') }}</option>
                                <option value="experience">{{ __('Sort by Experience') }}</option>
                                <option value="rate">{{ __('Sort by Rate') }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Accountants Grid -->
                    <div class="row" id="accountantsGrid">
                        @foreach($accountants as $accountant)
                            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                                <div class="card h-100 accountant-card">
                                    <div class="card-body text-center">
                                        <div class="avatar avatar-xl mb-3">
                                            @if($accountant->avatar)
                                                <img src="{{ $accountant->profile }}" alt="{{ $accountant->name }}" class="avatar-img rounded-circle">
                                            @else
                                                <div class="avatar-initial bg-primary rounded-circle">
                                                    {{ strtoupper(substr($accountant->name, 0, 1)) }}
                                                </div>
                                            @endif
                                        </div>
                                        <h5 class="card-title">{{ $accountant->name }}</h5>
                                        <div class="mb-1">
                                            @if($accountant->accountant_type === 'firm')
                                                <span class="badge bg-primary">{{ __('Firm') }}</span>
                                            @else
                                                <span class="badge bg-info">{{ __('Individual') }}</span>
                                            @endif
                                        </div>
                                        <p class="text-muted mb-2">{{ $accountant->experience_years }} years experience</p>

                                        <div class="mb-3">
                                            <span class="badge bg-primary me-1">{{ $accountant->rating }}</span>
                                            <small class="text-muted">({{ $accountant->review_count }} reviews)</small>
                                        </div>

                                        <div class="mb-3">
                                            <strong>${{ $accountant->hourly_rate }}/hour</strong>
                                        </div>

                                        <div class="mb-3">
                                            <div class="specialties">
                                                @foreach(array_slice($accountant->specialties, 0, 2) as $specialty)
                                                    <span class="badge bg-light text-dark me-1">{{ $specialty }}</span>
                                                @endforeach
                                                @if(count($accountant->specialties) > 2)
                                                    <span class="badge bg-light text-dark">+{{ count($accountant->specialties) - 2 }} more</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <a href="{{ route('accountant.marketplace.show', $accountant->id) }}" class="btn btn-outline-primary btn-sm">
                                                {{ __('View Profile') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $accountants->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Search functionality
    $('#searchInput').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();
        $('.accountant-card').each(function() {
            var cardText = $(this).text().toLowerCase();
            if (cardText.indexOf(searchTerm) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Specialty filter
    $('#specialtyFilter').on('change', function() {
        var selectedSpecialty = $(this).val();
        if (selectedSpecialty === '') {
            $('.accountant-card').show();
        } else {
            $('.accountant-card').each(function() {
                var specialties = $(this).find('.specialties .badge').map(function() {
                    return $(this).text();
                }).get();

                if (specialties.includes(selectedSpecialty)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
    });

    // Sort functionality
    $('#sortBy').on('change', function() {
        var sortBy = $(this).val();
        var cards = $('.accountant-card').parent().get();

        cards.sort(function(a, b) {
            var aVal, bVal;

            if (sortBy === 'rating') {
                aVal = parseFloat($(a).find('.badge.bg-primary').first().text());
                bVal = parseFloat($(b).find('.badge.bg-primary').first().text());
            } else if (sortBy === 'experience') {
                aVal = parseInt($(a).find('p.text-muted').first().text());
                bVal = parseInt($(b).find('p.text-muted').first().text());
            } else if (sortBy === 'rate') {
                aVal = parseInt($(a).find('strong').first().text().replace('$', '').replace('/hour', ''));
                bVal = parseInt($(b).find('strong').first().text().replace('$', '').replace('/hour', ''));
            }

            return bVal - aVal; // Descending order
        });

        $('#accountantsGrid').html(cards);
    });
});
</script>
@endpush

<style>
.accountant-card {
    transition: transform 0.2s;
}

.accountant-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.avatar-initial {
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: bold;
    color: white;
}
</style>
