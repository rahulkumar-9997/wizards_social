@extends('backend.pages.layouts.master')
@section('title', 'Instagram Dashboard')
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous" />
@endpush
@section('main-content')
<div class="container-fluid">
    <div class="row">
        @include('backend.pages.layouts.second-sidebar', [
            'selectedInstagramId' => $instagram['id'] ?? null
        ])
        <div class="col-xl-9 export_pdf_report">
            <div class="row mb-2">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-1">
                            <h4 class="card-title mb-0">Facebook Integration - Connected</h4>
                            <button id="downloadPdf" class="btn btn-outline-primary pdf-download-btn no-print">
                                <i class="bx bx-download"></i> Download PDF Report
                            </button>
                        </div>
                        <div class="card-body d-flex align-items-center">
                            <img src="{{ $instagram['profile_picture_url'] ?? '' }}" width="100" height="100" class="me-3" alt="Profile">
                            <div>
                                <h3 class="mb-1 fw-bold">{{ $instagram['name'] ?? '' }}</h3>
                                <p class="text-muted mb-1">{{ $instagram['username'] ?? '' }}</p>
                                <p class="mb-2">{!! nl2br(e($instagram['biography'] ?? '')) !!}</p>
                                <div class="d-flex gap-4">
                                    <span><strong>{{ number_format($instagram['media_count'] ?? 0) }}</strong> posts</span>
                                    <span><strong>{{ number_format($instagram['followers_count'] ?? 0) }}</strong> followers</span>
                                    <span><strong>{{ number_format($instagram['follows_count'] ?? 0) }}</strong> following</span>
                                </div>
                            </div>
                            <div class="ms-auto">
                                <a href="{{ route('facebook.index') }}" class="btn btn-outline-primary">
                                    <i class="fab fa-facebook"></i> Back to Facebook
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-xxl-12">
                    <div id="insta_face_dashboard">

                    </div>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-xxl-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-1">
                            <h4 class="card-title mb-0">
                                Top 10 Cities Audience
                                <i id="audienceByCitiesTitle"
                                    class="bx bx-question-mark text-primary"
                                    style="cursor: pointer; font-size: 18px;"
                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                    data-bs-custom-class="warning-tooltip">
                                </i>
                            </h4>
                            <select id="timeframe" class="form-select form-select-sm w-auto">
                                <option value="this_month" selected>This Month</option>
                                <option value="this_week">This Week</option>
                            </select>
                        </div>
                        <div class="card-body">
                            <div id="geolocationContainer">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-1">
                            <h4 class="card-title mb-0">
                                Audience By Age Group
                                <i id="audienceByAgeGroup"
                                    class="bx bx-question-mark text-primary"
                                    style="cursor: pointer; font-size: 18px;"
                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                    data-bs-custom-class="info-tooltip">
                                </i>
                            </h4>
                            <select id="ageTimeframe" class="form-select form-select-sm" style="width: 150px;">
                                <option value="this_week">This Week</option>
                                <option value="this_month" selected>This Month</option>
                            </select>
                        </div>
                        <div class="card-body">
                            <div id="audienceAgeGroupContainer">
                                <canvas id="audienceAgeGroupChart" height="450"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--Instagram reach days wise -->
            <div class="row mb-2">
                <div class="col-xxl-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-1">
                            <h5 class="card-title mb-0">Profile Reach Per Day
                                <i id="profileReachTitle"
                                    class="bx bx-question-mark text-primary"
                                    style="cursor: pointer; font-size: 18px;"
                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                    data-bs-custom-class="danger-tooltip">
                                </i>
                            </h5>
                            <small id="reachDateRange" class="text-muted"></small>
                        </div>
                        <div class="card-body">
                            <div id="reachDaysContainer">
                                <canvas id="reachDaysChart" height="450"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-1">
                            <h5 class="card-title mb-0">
                                Instagram Views
                                <i id="viewDateRangeTitle"
                                    class="bx bx-question-mark text-primary"
                                    style="cursor: pointer; font-size: 18px;"
                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                    data-bs-custom-class="success-tooltip">
                                </i>
                            </h5>
                            <span id="viewDateRange" class="text-muted small"></span>
                        </div>
                        <div class="card-body">
                            <div id="viewDaysContainer">

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-xxl-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-1">
                            <h4 class="card-title mb-0">Instagram Post</h4>
                        </div>
                        <div class="card-body">
                            <div class="instagram_post">
                                <div class="col-lg-12">
                                    <div id="post-filter" class="filter-box">
                                        <div class="d-flex flex-wrap align-items-center bg-white p-2 gap-1">
                                            <div class="d-flex align-items-center border-end pe-1">
                                                <select id="media-type-filter" class="form-select form-select-md">
                                                    <option value="">All Types</option>
                                                    <option value="CAROUSEL_ALBUM">Photos</option>
                                                    <option value="VIDEO">Video</option>
                                                    <option value="REELS">Reels</option>
                                                </select>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <input type="search" id="post-search" class="form-control form-control-md" placeholder="Search by ID or Caption">
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button id="reset-filters" class="btn btn-danger">
                                                    <i class="bx bx-reset me-1"></i> Reset
                                                </button>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <div id="instagram_post">

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection
    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <script>        
        window.facebook_base_url = "{{ url('facebook-summary') }}";
    </script>
    @endpush