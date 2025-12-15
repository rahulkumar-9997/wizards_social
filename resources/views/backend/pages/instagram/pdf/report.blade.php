@extends('backend.pages.layouts.master')
@section('title', 'Instagram Dashboard')
@push('styles')
<style>
    .metric-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        padding: 10px;
        transition: all 0.3s ease;
        height: 100%;
    }

    .metric-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
    }

    .metric-header {
        font-size: 22px;
        font-weight: 600;
        color: #313b5e;
        border-bottom: 2px solid #f1f1f1;
    }

    .metric-body h3 {
        font-weight: 700;
        font-size: 1.5rem;
    }

    .metric-body h4 {
        font-weight: 600;
        font-size: 1.2rem;
    }

    .metric-body table {
        width: 100%;
        border-collapse: collapse;
    }

    .metric-body table td,
    .metric-body table th {
        padding: 8px;
    }



    .stats-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        text-align: center;
    }

    .account-enga {
        flex: 1;
        padding: 8px;
    }

    .account-enga h4 {
        font-size: 1.4rem;
        font-weight: 700;
    }

    .account-enga p {
        font-size: 0.9rem;
        color: #6c757d;
    }

    .positive {
        background-color: #28a745 !important;
        color: #fff !important;
    }

    .positive h4,
    .negative h4 {
        color: #fff;
    }

    .negative {
        background-color: #dc3545 !important;
        color: #fff !important;
    }

    .neutral {
        background-color: #f1f3f5 !important;
        color: #333 !important;
        border-radius: 8px;
    }

    .metrics-table {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        background: #fff;
    }

    .metrics-table th {
        background: linear-gradient(135deg, #111, #333);
        color: #fff;
        font-weight: 600;
        /* text-transform: uppercase; */
        /* font-size: 13px; */
        padding: 5px;
    }

    .metrics-table td {
        vertical-align: middle;
        font-size: 14px;
        padding: 10px;
    }

    .metrics-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .metric-section-header {
        background: #222;
        color: #fff;
        text-align: center;
        font-size: 14px;
        font-weight: 500;
        letter-spacing: .5px;
    }

    .highlight {
        font-weight: 600;
        color: #007bff;
    }

    .page-break {
        page-break-before: always;
        break-before: page;
        height: 40px;
        background: transparent !important;
    }
</style>

@endpush
@section('main-content')
<div class="container-fluid">
    <div class="row">
        @include('backend.pages.layouts.second-sidebar', [
        'selectedInstagramId' => $instagram['id'] ?? null
        ])
        <div class="col-xl-9 export_pdf_report">
            <div class="pdf-header" style="display: none;">
                <div class="header-content" style="padding: 10px; border-bottom: 5px solid #fd7e03; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h1 style="font-size: 30px; color: #000000; margin: 0; font-weight: bold;">Instagram Report</h1>
                            <p style="font-size: 18px; color: #000000; margin: 5px 0 0 0;">For the Date Range of: 
                                <span id="report-date">
                                    14-Nov-25 to 11-Dec-25 
                                </span>
                            </p>
                        </div>
                        <div style="text-align: right;">
                            <img src="{{ asset('backend/assets/logo.png') }}" style="width:177px; height:45px;" class="logo-lg" alt="logo light" loading="lazy">
                        </div>
                    </div>
                </div>                
            </div>
            <div class="pdf-footer" style="display: none;">
                <div class="header-content" style="padding: 10px; border-top: 5px solid #fd7e03; margin-top: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="margin-right: 5px;">
                                <img src="{{ asset('backend/assets/phone-icon.png') }}">
                            </div>
                            <div>
                                <h4 style="font-size: 18px; color: #000000; margin: 0px 0 0 0;">
                                    +91-7339474554
                                </h4>
                            </div>
                            
                        </div>
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="margin-right: 5px;">
                                    <img src="{{ asset('backend/assets/location-icon.png') }}">
                                </div>
                                <div>
                                    <h4 style="font-size: 16px; color: #000000; margin: 0px 0 0 0; line-height: 20px;">
                                        Wizards Next LLP, Sigra Mahmoorganj Road,<br> Near
                                        Santoor Hotel, Varanasi, 221010
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>                
            </div>
            <div class="row mb-2 pdf-content">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-1">
                            <h4 class="card-title mb-0">Instagram Integration - Connected</h4>
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
            <div class="row">
                <div class="col-xxl-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-1">
                            <h5 class="card-title mb-0">
                                Total Views
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
            <div class="page-break"></div>
            <!--Total interaction section-->
            <div class="row mb-2">
                <div class="col-xxl-12">
                    <div id="total_interactions_dashboard">

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
                <div class="page-break"></div>
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
            </div>
            <div class="page-break"></div>
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
   

    @endpush