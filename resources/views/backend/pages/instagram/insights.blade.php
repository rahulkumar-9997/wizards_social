@extends('backend.pages.layouts.master')
@section('title', 'Post Insights - ' . ($instagram['username'] ?? ''))

@push('styles')
<link rel="stylesheet" type="text/css" href="https://www.gdsons.co.in/public/backend/assets/js/daterangepicker/daterangepicker.css" />
<style>
    .stat-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        border-left: 4px solid #3b82f6;
        border-right: 4px solid #f59e0b;
    }

    .stat-number {
        font-size: 20px;
        font-weight: bold;
        color: #3b82f6;
        margin-bottom: 5px;
    }

    .chart-container {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid #e9ecef;
    }

    .caption-container {
        position: relative;
        padding-bottom: 40px;
    }

    .caption-content {
        max-height: 80px;
        overflow: hidden;
        transition: max-height 0.3s ease;
        position: relative;
    }

    .caption-content.expanded {
        max-height: none;
    }

    .caption-content:not(.expanded)::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 30px;
        background: linear-gradient(transparent, #d1ecf1);
        pointer-events: none;
    }

    .read-more-btn {
        position: absolute;
        bottom: 10px;
        left: 15px;
        text-decoration: none;
        font-size: 0.875rem;
    }

    .read-more-btn:hover {
        text-decoration: underline;
    }
</style>
@endpush

@section('main-content')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title flex-grow-1">
                        Post Insights
                        <p class="mb-0"><strong>Posted:</strong>
                            <span class="text-warning">
                                {{ $postData['timestamp'] }}
                            </span>
                        </p>
                    </h4>
                    <a href="{{ $postData['permalink'] }}" target="_blank" class="btn btn-primary btn-sm">
                        <i class="fab fa-instagram"></i> View Post
                    </a>
                    <a href="{{ route('instagram.show', $instagram['id']) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="post-media-section" style="max-height: 600px; overflow-y:auto;">
                                @if($postData['media_type'] === 'VIDEO' || $postData['media_type'] === 'REEL')
                                <div class="test-video-section position-relative">
                                    <div class="embed-responsive-div embed-responsive-16by9">
                                        <video autoplay controls class="embed-responsive-item lazy-video">
                                            <source src="{{ $postData['media_url'] }}" type="video/mp4">
                                        </video>
                                    </div>
                                </div>
                                @elseif($postData['media_type'] === 'CAROUSEL_ALBUM')
                                <div id="postCarousel-{{ $postData['id'] }}" class="carousel carousel-dark slide" data-bs-ride="carousel">
                                    <div class="carousel-indicators">
                                        @foreach($postData['carousel_media'] as $index => $media)
                                        <button type="button" data-bs-target="#postCarousel-{{ $postData['id'] }}"
                                            data-bs-slide-to="{{ $index }}"
                                            class="{{ $index === 0 ? 'active' : '' }}"
                                            aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                                            aria-label="Slide {{ $index + 1 }}"></button>
                                        @endforeach
                                    </div>
                                    <div class="carousel-inner" style="border-radius: 8px;">
                                        @foreach($postData['carousel_media'] as $index => $media)
                                        <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                            @if($media['media_type'] === 'VIDEO')
                                            <div class="test-video-section position-relative">
                                                <div class="embed-responsive-div embed-responsive-16by9">
                                                    <video controls class="embed-responsive-item lazy-video">
                                                        <source src="{{ $media['media_url'] }}" type="video/mp4">
                                                        Your browser does not support the video tag.
                                                    </video>
                                                </div>
                                            </div>
                                            @else
                                            <img src="{{ $media['media_url'] }}"
                                                class="d-block w-100"
                                                alt="Carousel image {{ $index + 1 }}">
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                    <button class="carousel-control-prev" type="button" data-bs-target="#postCarousel-{{ $postData['id'] }}" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Previous</span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#postCarousel-{{ $postData['id'] }}" data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Next</span>
                                    </button>
                                </div>
                                <div class="text-center mt-2">
                                    <h4>
                                        <strong>
                                            {{ count($postData['carousel_media']) }}
                                        </strong>
                                        items in carousel
                                    </h4>
                                </div>
                                @else
                                <img src="{{ $postData['media_url'] }}"
                                    alt="Post"
                                    style="max-width: 100%; border-radius: 8px;">
                                @endif
                                <div class="mt-1">
                                    @if(!empty($postData['caption']))
                                    <div class="alert alert-info caption-container">
                                        <div class="caption-content">
                                            <span class="caption-text">{{ $postData['caption'] }}</span>
                                        </div>
                                        @if(strlen($postData['caption']) > 100)
                                        <button class="btn btn-link btn-sm p-0 mt-2 read-more-btn" data-state="more">
                                            Read More <i class="ti ti-caret-down ms-1"></i>
                                        </button>
                                        @endif
                                    </div>
                                    @else
                                    <div class="alert alert-info">
                                        <strong>Caption:</strong> No caption available.
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="post-stats-section mb-1">
                                <div class="row">
                                    <div class="col-12">
                                        <h3 class="mb-1">Overview</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                @php
                                $statItems = [
                                ['value' => $postData['likes'], 'label' => 'Likes', 'color' => '#3b82f6'],
                                ['value' => $postData['comments'], 'label' => 'Comments', 'color' => '#10b981'],
                                ['value' => $postData['reach'], 'label' => 'Reach', 'color' => '#8b5cf6'],
                                ['value' => $postData['shares'], 'label' => 'Shares', 'color' => '#8b5cf6'],
                                ];

                                if ($postData['media_type'] === 'REEL') {
                                // Facebook removed "plays" metric, so don't use it.
                                $statItems[] = [
                                'value' => $postData['avg_watch_time_formatted'],
                                'label' => 'Avg Watch Time',
                                'color' => '#0ea5e9'
                                ];
                                $statItems[] = [
                                'value' => $postData['total_watch_time_formatted'],
                                'label' => 'Total Watch Time',
                                'color' => '#06b6d4'
                                ];
                                } else {
                                if (!empty($postData['impressions']) && $postData['impressions'] > 0) {
                                $statItems[] = [
                                'value' => $postData['impressions'],
                                'label' => 'Impressions',
                                'color' => '#f59e0b'
                                ];
                                }
                                if (!empty($postData['video_views']) && $postData['video_views'] > 0) {
                                $statItems[] = [
                                'value' => $postData['video_views'],
                                'label' => 'Video Views',
                                'color' => '#0ea5e9'
                                ];
                                }
                                }

                                $statItems[] = [
                                'value' => $postData['engagement_rate'] . '%',
                                'label' => 'Engagement Rate',
                                'color' => '#ef4444'
                                ];
                                $statItems[] = [
                                'value' => $postData['saves'],
                                'label' => 'Saves',
                                'color' => '#06b6d4'
                                ];
                                
                                $statItems[] = [
                                'value' => $postData['total_interactions'],
                                'label' => 'Total Interactions',
                                'color' => '#f97316'
                                ];
                                @endphp

                                @foreach($statItems as $item)
                                <div class="col-md-4 mb-3">
                                    <div class="stat-card" style="border-left-color: {{ $item['color'] }};">
                                        <div class="stat-number" style="color: {{ $item['color'] }};">
                                            {{ is_numeric($item['value']) ? number_format($item['value']) : $item['value'] }}
                                        </div>
                                        <h4 class="text-dark mt-1 mb-0">{{ $item['label'] }}</h4>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="instagram-comment-section mt-1">
                                        <div id="ig-comments-section" data-media-id="{{ $postData['id'] }}">
                                            <h4 class="mb-1">Comments</h4>
                                            <form id="ig-comment-form" class="mb-1">
                                                @csrf
                                                <div class="input-group">
                                                    <input type="text" id="ig-comment-message" class="form-control" placeholder="Write a comment..." required>
                                                    <button class="btn btn-primary" type="submit">Post</button>
                                                </div>
                                            </form>
                                            <div id="ig-comments-list" class="border rounded p-2" style="max-height:500px;overflow-y:auto;">
                                                <p class="text-muted">Loading comments...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Simple Graphs Section -->
    <div class="row mb-2">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Views</h4>
                        <div class="d-flex align-items-center border-end pe-1">
                            <p class="mb-0 me-2 text-dark-grey f-14">Duration:</p>
                            <input type="text" class="form-control form-control-sm text-dark border-0 f-14" id="daterange" name="daterange" placeholder="Start Date To End Date" autocomplete="off">
                        </div>
                        <!-- <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary active time-filter" data-range="week">
                                Last 7 Days
                            </button>
                            <button type="button" class="btn btn-outline-primary time-filter" data-range="month">
                                Last 30 Days
                            </button>
                        </div> -->
                    </div>
                </div>
                <div class="card-body">
                    <!-- Performance Chart -->
                    <div class="chart-container">
                        <h6>Performance Overview</h6>
                        <div id="performance-chart" style="height: 300px;"></div>
                    </div>

                    <!-- Engagement Chart -->
                    <div class="chart-container">
                        <h6>Engagement Metrics</h6>
                        <div id="engagement-chart" style="height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript" src="https://www.gdsons.co.in/public/backend/assets/js/daterangepicker/daterangepicker.min.js"></script>
<script src="https://www.gdsons.co.in/public/backend/assets/js/rahul-jquery-ui.min.js"></script>
<!-- <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script> -->
<script>
    $(document).ready(function() {
        let currentRange = 'week';
        let performanceChart, engagementChart;

        // Load initial graphs
        loadGraphs(currentRange);

        // Time filter buttons
        $('.time-filter').on('click', function() {
            $('.time-filter').removeClass('active');
            $(this).addClass('active');
            currentRange = $(this).data('range');
            loadGraphs(currentRange);
        });

        function loadGraphs(timeRange) {
            // Show loading
            $('#performance-chart').html('<div class="text-center p-4"><div class="spinner-border text-primary"></div></div>');
            $('#engagement-chart').html('<div class="text-center p-4"><div class="spinner-border text-primary"></div></div>');

            $.ajax({
                url: "{{ route('instagram.post.graph.data') }}",
                type: "GET",
                data: {
                    time_range: timeRange
                },
                success: function(response) {
                    if (response.success) {
                        renderCharts(response.data);
                    } else {
                        showError(response.error);
                    }
                },
                error: function() {
                    showError('Failed to load graph data');
                }
            });
        }

        function renderCharts(data) {
            // Destroy existing charts
            if (performanceChart) {
                performanceChart.destroy();
            }
            if (engagementChart) {
                engagementChart.destroy();
            }

            // Performance Chart - Now using reach instead of engagement
            performanceChart = new ApexCharts(document.querySelector("#performance-chart"), {
                series: [{
                        name: 'Impressions',
                        data: data.impressions
                    },
                    {
                        name: 'Reach',
                        data: data.reach
                    }
                ],
                chart: {
                    type: 'line',
                    height: 300,
                    toolbar: {
                        show: true
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                xaxis: {
                    categories: data.dates
                },
                colors: ['#3b82f6', '#10b981'],
                legend: {
                    position: 'top'
                }
            });
            performanceChart.render();

            // Engagement Chart - Now showing both likes and total engagement
            engagementChart = new ApexCharts(document.querySelector("#engagement-chart"), {
                series: [{
                        name: 'Likes',
                        data: data.likes
                    },
                    {
                        name: 'Total Engagement',
                        data: data.engagement
                    }
                ],
                chart: {
                    type: 'bar',
                    height: 300,
                    toolbar: {
                        show: true
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: false,
                    }
                },
                xaxis: {
                    categories: data.dates
                },
                colors: ['#f59e0b', '#ef4444']
            });
            engagementChart.render();
        }

        function showError(message) {
            $('#performance-chart').html('<div class="alert alert-danger">' + message + '</div>');
            $('#engagement-chart').html('<div class="alert alert-danger">' + message + '</div>');
        }

        // Load graphs on page load
        loadGraphs('week');
    });
</script>
<script>
    $('#daterange').daterangepicker({
        opens: 'right',
        ranges: {
            'Today': [moment(), moment()],
            'Last 7 Days': [moment().subtract(7, 'days'), moment()],
            'Last 15 Days': [moment().subtract(15, 'days'), moment()],
            'Last 28 Days': [moment().subtract(28, 'days'), moment()],
            'Last 90 Days': [moment().subtract(89, 'days'), moment()],
            'Last 60 Days': [moment().subtract(59, 'days'), moment()],            
            'This Week': [moment().subtract(6, 'months'), moment()],
            'This Month': [moment().subtract(6, 'months'), moment()],
            'This Year': [moment().subtract(1, 'year'), moment()],
        },
        autoUpdateInput: false,
        locale: {
            format: 'YYYY-MM-DD',
            cancelLabel: 'Clear',
        }
    });
    $(document).ready(function() {
        // Read More/Less functionality
        $(document).on('click', '.read-more-btn', function() {
            const $btn = $(this);
            const $container = $btn.closest('.caption-container');
            const $content = $container.find('.caption-content');
            const $text = $container.find('.caption-text');

            if ($btn.data('state') === 'more') {
                $content.addClass('expanded');
                $btn.html('Read Less <i class="fas fa-chevron-up ms-1"></i>');
                $btn.data('state', 'less');
            } else {
                $content.removeClass('expanded');
                $btn.html('Read More <i class="fas fa-chevron-down ms-1"></i>');
                $btn.data('state', 'more');
            }
        });
        $('.caption-container').each(function() {
            const $container = $(this);
            const $content = $container.find('.caption-content');
            const $text = $container.find('.caption-text');
            if ($text.height() > $content.height()) {
                $container.find('.read-more-btn').show();
            }
        });
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const section = document.getElementById('ig-comments-section');
    const mediaId = section.dataset.mediaId;
    const commentsList = document.getElementById('ig-comments-list');
    const form = document.getElementById('ig-comment-form');
    const token = document.querySelector('meta[name="csrf-token"]').content;

    // 🔹 Load comments from controller (HTML)
    function loadComments() {
        commentsList.innerHTML = '<p class="text-muted">Loading comments...</p>';
        fetch(`/instagram/${mediaId}/comments/html`)
            .then(res => res.json())
            .then(data => {
                commentsList.innerHTML = data.html || '<p class="text-danger">Failed to load comments.</p>';
            })
            .catch(() => {
                commentsList.innerHTML = '<p class="text-danger">Error loading comments.</p>';
            });
    }

    // 🔹 Post comment using AJAX
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const message = document.getElementById('ig-comment-message').value.trim();
        if (!message) return;

        fetch(`/instagram/${mediaId}/comments`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ message })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('ig-comment-message').value = '';
                loadComments(); // reload list
            } else {
                alert(data.error || 'Failed to post comment.');
            }
        })
        .catch(() => alert('Network error while posting comment.'));
    });

    // Initial load
    loadComments();
});
</script>

@endpush