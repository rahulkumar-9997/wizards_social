@extends('backend.pages.layouts.master')
@section('title', 'Instagram Dashboard')
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
    <!-- <div class="row mb-2">
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="avatar-md bg-primary rounded">
                                <i class="bx bxs-user-check avatar-title fs-24 text-white"></i>
                            </div>
                        </div>
                        <div class="col-6 text-end">
                            <p class="text-muted mb-0 text-truncate">Followers</p>
                            <h3 class="text-dark mt-1 mb-0">
                                {{ number_format($instagram['followers_count'] ?? 0) }}
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="avatar-md bg-success rounded">
                                <i class="bx bx-images avatar-title fs-24 text-white"></i>
                            </div>
                        </div>
                        <div class="col-6 text-end">
                            <p class="text-muted mb-0 text-truncate">Total Posts</p>
                            <h3 class="text-dark mt-1 mb-0">
                                {{ $totalPosts }}
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> -->
    <div class="row mb-4">
        <div class="col-xxl-12">
            <div class="card">
                <div class="card-header text-white">
                    <h4 class="mb-2">
                        Performance                       
                    </h4>
                     <!-- <h5>17 September 2025 - 14 October 2025</h5> -->
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Instagram Daily Insights</h4>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-light active filter-btn" data-filter="week">week</button>
                            <button type="button" class="btn btn-sm btn-outline-light" data-filter="day">day</button>
                            
                            <button type="button" class="btn btn-sm btn-outline-light" data-filter="days_28">days_28</button>
                            <button type="button" class="btn btn-sm btn-outline-light" data-filter="month">month</button>
                            <button type="button" class="btn btn-sm btn-outline-light" data-filter="lifetime">lifetime</button>
                            <button type="button" class="btn btn-sm btn-outline-light" data-filter="total_over_range">total_over_range</button>
                        </div>
                    </div>
                    <div class="map-section">
                        <div id="likes_graph" style="min-height: 350px;"></div>
                    </div>
                    <div class="col-12 mb-3">
                        <h5 class="mb-3">Post</h5>
                    </div>
                    <div id="instagram-media-table">
                        @include('backend.pages.instagram.partials.instagram-media-table', [
                        'media' => $paginatedMedia,
                        'paginatedMedia' => $paginatedMedia
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection

    @push('scripts')
    <script>
$(document).ready(function() {
    const chartEl = $('#likes_graph')[0];
    let chart;

    // Load graph data
    function loadGraph(period = 'week') {
        const instagramId = "{{ $instagram['id'] ?? 0 }}";

        $.get(`{{ route('instagram.metrics.graph', $instagram['id'] ?? 0) }}`, { period: period })
            .done(function(data) {
                if (data.error) {
                    chartEl.innerHTML = `<p class="text-danger">${data.error}</p>`;
                    return;
                }

                const options = {
                    chart: { type: 'line', height: 350, toolbar: { show: false }, zoom: { enabled: false }, foreColor: '#ccc' },
                    stroke: { curve: 'smooth', width: 2 },
                    markers: { size: 3 },
                    grid: { borderColor: '#333', strokeDashArray: 3 },
                    xaxis: { categories: data.dates, labels: { rotate: -45, style: { fontSize: '12px' } } },
                    yaxis: { title: { text: 'Count', style: { color: '#ccc' } }, min: 0 },
                    legend: { position: 'top', horizontalAlign: 'right', labels: { colors: '#000000ff' } },
                    colors: ['#3b82f6', '#10b981', '#f43f5e', '#f59e0b'],
                    series: [
                        { name: 'Reach', data: data.reach },
                        { name: 'Likes', data: data.likes },
                        { name: 'Comments', data: data.comments },
                        { name: 'Views', data: data.views }
                    ],
                    tooltip: { theme: 'dark', shared: true, intersect: false },
                    title: { text: 'Instagram Media Insights Trends', align: 'left', style: { color: '#fff', fontSize: '16px' } }
                };

                if (chart) {
                    chart.updateOptions(options);
                } else {
                    chart = new ApexCharts(chartEl, options);
                    chart.render();
                }
            })
            .fail(function(err) {
                console.error('Error loading graph:', err);
                chartEl.innerHTML = `<p class="text-danger">Failed to load data</p>`;
            });
    }

    // Button click event
    $('.btn-outline-light').on('click', function() {
        const period = $(this).data('filter');
        $('.btn-outline-light').removeClass('active');
        $(this).addClass('active');
        loadGraph(period);
    });

    // Initial load
    loadGraph('week');
});



        /*Pagination */
        // AJAX Pagination with jQuery
        $(document).ready(function() {
            $(document).on('click', '.pagination a', function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                fetchInstagramMedia(url);
            });

            function fetchInstagramMedia(url) {
                $('#instagram-media-table').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');
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
                            throw new Error(data.error || 'Unknown error occurred');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        let errorMessage = 'Error loading content';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        } else {
                            errorMessage = error;
                        }
                        $('#instagram-media-table').html('<div class="alert alert-danger">' + errorMessage + '</div>');
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