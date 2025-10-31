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
</style>
@endpush
@section('main-content')
<div class="container-fluid">
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
        <div class="col-xxl-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title mb-0">Top 10 Cities Audience</h4>
                    <select id="timeframe" class="form-select form-select-sm w-auto">
                        <option value="this_month" selected>This Month</option>
                        <option value="this_week">This Week</option>
                    </select>
                </div>
                <div class="card-body">
                    <div id="topLocationsContainer">
                        <canvas id="topLocationsChart" height="450"></canvas>
                    </div>
                </div>
            </div>
        </div> 
        <div class="col-xxl-6">
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
    <div class="row mb-2">
        <div class="col-xxl-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title mb-0">Instagram Ads</h4>                    
                </div>
                <div class="card-body">
                    <div class="instagram_ads">

                    </div>
                </div>
            </div>
        </div>              
    </div>
    @endsection

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <script>
        window.instagramTopLocationUrl = "{{ url('/instagram/top-locations/' . $instagram['id']) }}";
    </script>
    <script>
        window.instagramAudienceAgeUrl = "{{ route('instagram.audienceAgeGender', $instagram['id']) }}";
    </script>
    <script src="{{ asset('backend/assets/js/pages/instagram-top-location.js') }}"></script>
    <script src="{{ asset('backend/assets/js/pages/instagram-audience-age.js') }}"></script>
    <script>
        $(document).ready(function() {
            const id = "{{ $instagram['id'] }}";
            const defaultStart = moment().subtract(30, 'days');
            const defaultEnd = moment();
            $('.daterange').daterangepicker({
                opens: 'right',
                startDate: defaultStart,
                endDate: defaultEnd,
                maxDate: moment(),
                dateLimit: {
                    days: 30
                },
                ranges: {
                    'Today': [moment(), moment()],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 15 Days': [moment().subtract(14, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()]
                },
                autoUpdateInput: true,
                locale: {
                    format: 'YYYY-MM-DD',
                    cancelLabel: 'Clear',
                }
            }, function(start, end) {
                $('.daterange').val(`${start.format('YYYY-MM-DD')} - ${end.format('YYYY-MM-DD')}`);
            });
            $('.daterange').val(`${defaultStart.format('YYYY-MM-DD')} - ${defaultEnd.format('YYYY-MM-DD')}`);

            loadInstagramData(id, defaultStart.format('YYYY-MM-DD'), defaultEnd.format('YYYY-MM-DD'));
            $('.daterange').on('apply.daterangepicker', function(ev, picker) {
                const startDate = picker.startDate.format('YYYY-MM-DD');
                const endDate = picker.endDate.format('YYYY-MM-DD');
                $(this).val(`${startDate} - ${endDate}`);
                loadInstagramData(id, startDate, endDate);
            });
            $('.daterange').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                loadInstagramData(
                    id,
                    moment().subtract(30, 'days').format('YYYY-MM-DD'),
                    moment().format('YYYY-MM-DD')
                );
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

    <script>
        /*Pagination */
        $(document).ready(function() {
            $(document).on('click', '.page-link', function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                fetchInstagramMedia(url);
            });

            function fetchInstagramMedia(url) {
                $('#instagram-media-table').html(`
                    <div class="text-center p-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>                    
                `);

                $.ajax({
                    url: url,
                    type: "GET",
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(data) {
                        if (data.success) {
                            $('#instagram-media-table').html(data.html);
                            window.history.pushState({}, '', url);
                        } else {
                            $('#instagram-media-table').html('<div class="alert alert-danger">Error: ' + (data.error || 'Unknown') + '</div>');
                        }
                    },
                    error: function(xhr) {
                        const msg = xhr.responseJSON?.error || 'Failed to load data.';
                        $('#instagram-media-table').html('<div class="alert alert-danger">' + msg + '</div>');
                    }
                });
            }

            $(window).on('popstate', function() {
                fetchInstagramMedia(window.location.href);
            });
        });

        /*Pagination */
    </script>
    @endpush