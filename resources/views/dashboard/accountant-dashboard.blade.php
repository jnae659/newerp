@extends('layouts.admin')
@section('page-title')
    {{ __('Accountant Dashboard') }}
@endsection

@php
    $logo = \App\Models\Utility::get_file('uploads/logo');
@endphp

@section('content')
    <div class="row">
        <!-- Total Companies -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-stats border-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col-5">
                            <div class="text-center">
                                <i class="fas fa-building fa-2x text-primary"></i>
                            </div>
                        </div>
                        <div class="col-7">
                            <div class="numbers">
                                <p class="card-category">{{ __('Total Companies') }}</p>
                                <h4 class="card-title">{{ $total_companies }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Invitations -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-stats border-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col-5">
                            <div class="text-center">
                                <i class="fas fa-envelope fa-2x text-warning"></i>
                            </div>
                        </div>
                        <div class="col-7">
                            <div class="numbers">
                                <p class="card-category">{{ __('Pending Invitations') }}</p>
                                <h4 class="card-title">{{ $pending_invitations }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Projects -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-stats border-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col-5">
                            <div class="text-center">
                                <i class="fas fa-tasks fa-2x text-success"></i>
                            </div>
                        </div>
                        <div class="col-7">
                            <div class="numbers">
                                <p class="card-category">{{ __('Active Projects') }}</p>
                                <h4 class="card-title">{{ $active_projects }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Revenue -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-stats border-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col-5">
                            <div class="text-center">
                                <i class="fas fa-dollar-sign fa-2x text-info"></i>
                            </div>
                        </div>
                        <div class="col-7">
                            <div class="numbers">
                                <p class="card-category">{{ __('Monthly Revenue') }}</p>
                                <h4 class="card-title">${{ number_format($monthly_revenue, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Companies -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ __('Recent Companies') }}</h5>
                </div>
                <div class="card-body">
                    @if($recent_companies->isEmpty())
                        <div class="text-center py-4">
                            <i class="fas fa-building fa-3x text-muted mb-3"></i>
                            <p class="text-muted">{{ __('No companies connected yet') }}</p>
                            <a href="{{ route('accountant.marketplace') }}" class="btn btn-primary">{{ __('Browse Accountant Marketplace') }}</a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Company Name') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recent_companies as $company)
                                        <tr>
                                            <td>{{ $company->name }}</td>
                                            <td>
                                                <span class="badge bg-success">{{ __('Active') }}</span>
                                            </td>
                                            <td>
                                                <a href="#" class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Upcoming Tasks -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ __('Upcoming Tasks') }}</h5>
                </div>
                <div class="card-body">
                    @if($upcoming_tasks->isEmpty())
                        <div class="text-center py-4">
                            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                            <p class="text-muted">{{ __('No upcoming tasks') }}</p>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($upcoming_tasks as $task)
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">{{ $task->name }}</h6>
                                        <small>{{ $task->due_date }}</small>
                                    </div>
                                    <p class="mb-1">{{ $task->description }}</p>
                                    <small>{{ $task->company_name }}</small>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Activities -->
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ __('Recent Activities') }}</h5>
                </div>
                <div class="card-body">
                    @if($recent_activities->isEmpty())
                        <div class="text-center py-4">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="text-muted">{{ __('No recent activities') }}</p>
                        </div>
                    @else
                        <div class="timeline">
                            @foreach($recent_activities as $activity)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">{{ $activity->title }}</h6>
                                        <p class="timeline-text">{{ $activity->description }}</p>
                                        <small class="text-muted">{{ $activity->created_at }}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
