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
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Instagram Daily Insights</h4>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-light active filter-btn" data-filter="7d">Last One Week</button>
                            <button type="button" class="btn btn-sm btn-outline-light" data-filter="1M">1M</button>
                            <button type="button" class="btn btn-sm btn-outline-light" data-filter="6M">6M</button>
                            <button type="button" class="btn btn-sm btn-outline-light" data-filter="1Y">1Y</button>
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
    let chart;

    function loadGraph(range = '7d') {
        $("#likes_graph").html('<div class="text-center p-5 text-muted">Loading graph...</div>');

        $.ajax({
            url: "{{ route('instagram.likes.graph', ['id' => $instagram['id'] ?? 0]) }}",
            type: "GET",
            data: { range: range },
            dataType: "json",
            success: function(data) {
                if (chart) chart.destroy();

                if (!data.dates || !data.dates.length) {
                    $("#likes_graph").html('<div class="text-center p-5 text-warning">No data available for this period</div>');
                    return;
                }

                const hasData = data.reach.some(v => v > 0) ||
                                data.impressions.some(v => v > 0) ||
                                data.profile_views.some(v => v > 0) ||
                                data.engagements.some(v => v > 0);

                if (!hasData) {
                    $("#likes_graph").html(`
                        <div class="text-center p-5 text-warning">
                            No insights data found.<br>
                            <small>
                                Possible reasons:<br>
                                - Not a Business/Creator Instagram account<br>
                                - Insights not enabled<br>
                                - No activity in this range
                            </small>
                        </div>
                    `);
                    return;
                }

                const options = {
                    series: [
                        { name: "Reach", type: "area", data: data.reach },
                        { name: "Impressions", type: "line", data: data.impressions },
                        { name: "Profile Views", type: "line", data: data.profile_views },
                        { name: "Engagements", type: "line", data: data.engagements }
                    ],
                    chart: {
                        height: 350,
                        type: "line",
                        toolbar: { show: true },
                        animations: { enabled: true }
                    },
                    stroke: { width: [3, 2, 2, 2], curve: 'smooth' },
                    colors: ["#22c55e", "#0d6efd", "#f59e0b", "#ef4444"],
                    fill: {
                        type: ['gradient', 'solid', 'solid', 'solid'],
                        gradient: {
                            shade: 'light',
                            type: 'vertical',
                            shadeIntensity: 0.5,
                            gradientToColors: ['#16a34a', '#0d6efd', '#f59e0b', '#ef4444'],
                            opacityFrom: 0.7,
                            opacityTo: 0.2,
                        }
                    },
                    xaxis: {
                        categories: data.dates,
                        labels: { rotate: -45, style: { fontSize: '11px' } }
                    },
                    yaxis: {
                        title: { text: 'Daily Insights' },
                        min: 0
                    },
                    legend: { show: true, position: 'top' },
                    tooltip: { shared: true, intersect: false },
                    dataLabels: { enabled: false },
                    grid: { borderColor: '#f1f1f1' }
                };

                $("#likes_graph").html('');
                chart = new ApexCharts(document.querySelector("#likes_graph"), options);
                chart.render();
            },
            error: function(xhr, status, error) {
                $("#likes_graph").html('<div class="text-center p-5 text-danger">Error loading graph.</div>');
                console.error("Error fetching Instagram graph:", error, xhr.responseText);
            }
        });
    }

    $(".filter-btn").on("click", function() {
        const range = $(this).data("filter");
        $(".filter-btn").removeClass("active");
        $(this).addClass("active");
        loadGraph(range);
    });

    loadGraph('7d');
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