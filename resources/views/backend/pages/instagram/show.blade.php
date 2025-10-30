@extends('backend.pages.layouts.master')
@section('title', 'Instagram Dashboard')
@push('styles')
<style>
    .metric-card {
        border-radius: 10px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        background: #fff;
        text-align: center;
    }

    .metric-header {
        background-color: #c2185b;
        color: #fff;
        padding: 6px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .metric-subheader {
        background-color: #7b1fa2;
        color: #fff;
        font-weight: 500;
        padding: 4px;
    }

    .metric-body {
        padding: 10px;
    }

    .metric-body h3 {
        font-weight: 700;
        font-size: 1.6rem;
    }

    .label-row {
        display: flex;
        justify-content: space-between;
        background: #000;
        color: #fff;
        font-size: 0.85rem;
        padding: 3px 8px;
    }

    .percent {
        font-weight: bold;
        color: #000000;
        padding: 6px;
        margin-top: 4px;
        font-size: 1rem;
    }

    .percent.red {
        background-color: #dc3545;
    }

    .percent.green {
        background-color: #28a745;
    }

    .stats-row {
        display: flex;
        justify-content: space-between;
        font-size: 0.85rem;
        margin-top: 5px;
        padding: 6px;
        gap: 5px;
    }

    .stats-row .account-enga {
        background: #dde4eba4;
        padding: 5px;
        border-radius: 5px;
    }

    .stats-row div {
        flex: 1;
    }

    .icon-metric {
        font-size: 22px;
        color: #c2185b;
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

    <div class="row mb-4">
        <div class="col-xxl-12">
            <div id="insta_face_dashboard">

            </div>
        </div>
    </div>
    @endsection

    @push('scripts')
    <script>
        $(document).ready(function() {
            const id = "{{ $instagram['id'] }}";
            $('.daterange').daterangepicker({
                opens: 'right',
                startDate: moment().subtract(28, 'days'),
                endDate: moment(),
                maxDate: moment(), // 🚫 Prevent future dates
                dateLimit: {
                    days: 30
                }, // ✅ Limit to 30 days max
                ranges: {
                    'Today': [moment(), moment()],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 15 Days': [moment().subtract(14, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()]
                },
                autoUpdateInput: false,
                locale: {
                    format: 'YYYY-MM-DD',
                    cancelLabel: 'Clear',
                }
            });

            // 🔹 Load initial data (default: last 30 days)
            loadInstagramData(
                id,
                moment().subtract(30, 'days').format('YYYY-MM-DD'),
                moment().format('YYYY-MM-DD')
            );

            // 🔹 On apply (user selects new date range)
            $('.daterange').on('apply.daterangepicker', function(ev, picker) {
                const startDate = picker.startDate.format('YYYY-MM-DD');
                const endDate = picker.endDate.format('YYYY-MM-DD');

                $(this).val(`${startDate} - ${endDate}`);

                // Load Instagram data for the selected range
                loadInstagramData(id, startDate, endDate);
            });

            // 🔹 On cancel (reset to last 30 days)
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
        </div>
    `;

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