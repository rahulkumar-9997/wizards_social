@extends('backend.pages.layouts.master')
@section('title', 'Instagram Dashboard')
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" 
          crossorigin="anonymous"/>
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
</style>
@endpush
@section('main-content')
<div class="container-fluid">
    <div class="row">
        @include('backend.pages.layouts.second-sidebar', [
            'selectedInstagramId' => $instagram['id'] ?? null
        ])
        <div class="col-xl-9">
            <div class="row mb-2">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header text-white">
                            <h4 class="card-title mb-0">Instagram Integration - Connected</h4>
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
                            <h4 class="card-title mb-0">Top 10 Cities Audience</h4>
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
                            <h4 class="card-title mb-0">Audience By Age Group</h4>
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
                <div class="col-xxl-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-1">
                            <h4 class="card-title mb-0">Profile Reach Per Day</h4>
                            <small id="reachDateRange" class="text-muted"></small>
                        </div>
                        <div class="card-body">
                            <div id="reachDaysContainer">
                                <canvas id="reachDaysChart" height="450"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-1">
                            <h5 class="card-title mb-0">
                                Instagram Views                        
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
            <!--Instagram reach days wise -->
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
        window.instagram_id = "{{ $instagram['id'] }}";
        window.instagramTopLocationUrl = "{{ route('instagram.top.location', $instagram['id']) }}";
        window.instagramAudienceAgeUrl = "{{ route('instagram.audienceAgeGender', $instagram['id']) }}";
        window.instagramFetchPostUrl = "{{ route('instagram.fetch.post', $instagram['id']) }}";
        window.instagramFetchReachDaysWise = "{{ route('instagram.fetch.reach-day-wise', $instagram['id']) }}";
        window.instagramFetchViewDaysWise = "{{ route('instagram.fetch.view-day-wise', $instagram['id']) }}";
        window.INSTAGRAM_BASE_URL = "{{ url('/instagram') }}";
    </script>

    <script src="{{ asset('backend/assets/js/pages/instagram-top-location.js') }}"></script>
    <script src="{{ asset('backend/assets/js/pages/instagram-audience-age.js') }}"></script>
    <script src="{{ asset('backend/assets/js/pages/instagram-post.js') }}"></script>
    <script src="{{ asset('backend/assets/js/pages/reach-days-wize-graphs.js') }}"></script>
    <script src="{{ asset('backend/assets/js/pages/instagram-view-days-wise.js') }}"></script>
    <script>
        $(document).ready(function() {
            const id = "{{ $instagram['id'] }}";
            const defaultStart = moment().subtract(29, 'days');
            const defaultEnd = moment().subtract(1, 'days');
            
            $('.daterange').daterangepicker({
                opens: 'right',
                startDate: defaultStart,
                endDate: defaultEnd,
                maxDate: moment().subtract(1, 'days'),
                dateLimit: {
                    days: 30
                },
                ranges: {
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(7, 'days'), moment().subtract(1, 'days')],
                    'Last 15 Days': [moment().subtract(15, 'days'), moment().subtract(1, 'days')],
                    'Last 30 Days': [moment().subtract(30, 'days'), moment().subtract(1, 'days')]
                },
                autoUpdateInput: true,
                locale: {
                    format: 'YYYY-MM-DD',
                    cancelLabel: 'Clear',
                },
                alwaysShowCalendars: true,
                showDropdowns: true,
            }, function(start, end) {
                $('.daterange').val(`${start.format('YYYY-MM-DD')} - ${end.format('YYYY-MM-DD')}`);
            });

            $('.daterange').val(`${defaultStart.format('YYYY-MM-DD')} - ${defaultEnd.format('YYYY-MM-DD')}`);
            loadInstagramPostData(id, defaultStart.format('YYYY-MM-DD'), defaultEnd.format('YYYY-MM-DD'));
            loadReachGraph(id, defaultStart.format('YYYY-MM-DD'), defaultEnd.format('YYYY-MM-DD'));
            loadViewGraph(id, defaultStart.format('YYYY-MM-DD'), defaultEnd.format('YYYY-MM-DD'));
            loadInstagramData(id, defaultStart.format('YYYY-MM-DD'), defaultEnd.format('YYYY-MM-DD'));
        
            $('.daterange').on('apply.daterangepicker', function(ev, picker) {
                const startDate = picker.startDate.format('YYYY-MM-DD');
                const endDate = picker.endDate.format('YYYY-MM-DD');
                $(this).val(`${startDate} - ${endDate}`);
                loadInstagramPostData(id, startDate, endDate);
                loadReachGraph(id, startDate, endDate);
                loadViewGraph(id, startDate, endDate);
                loadInstagramData(id, startDate, endDate);
            });

            $('.daterange').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                const defaultStart = moment().subtract(29, 'days').format('YYYY-MM-DD');
                const defaultEnd = moment().subtract(1, 'days').format('YYYY-MM-DD');
                loadInstagramPostData(id, defaultStart, defaultEnd);
                loadReachGraph(id, defaultStart, defaultEnd);
                loadViewGraph(id, defaultStart, defaultEnd);
                loadInstagramData(id, defaultStart, defaultEnd);
            });
        });

        function loadInstagramData(accountId, startDate, endDate) {
            const loadingHtml = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading performance data...</p>
            </div>`;
            $('#insta_face_dashboard').html(loadingHtml);
            $.ajax({
                url: `/instagram/fetch/${accountId}`,
                type: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(res) {
                    if (res.success) {
                        $('#insta_face_dashboard').html(res.html);
                    } else {
                        $('#insta_face_dashboard').html(`<div class="alert alert-danger">${res.message}</div>`);
                    }
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.error || 'Error fetching data';
                    $('#insta_face_dashboard').html(`<div class="alert alert-danger">${errorMessage}</div>`);
                }
            });
        }
    </script>

    @endpush