@extends('backend.pages.layouts.master')
@section('title', 'Post Insights - ' . ($instagram['username'] ?? ''))

@push('styles')
<style>
    .stat-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        border-left: 4px solid #3b82f6;
    }
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: #3b82f6;
        margin-bottom: 5px;
    }
    .stat-label {
        color: #6c757d;
        font-size: 0.9rem;
    }
    .chart-container {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid #e9ecef;
    }
</style>
@endpush

@section('main-content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('facebook.index') }}">Facebook</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('instagram.show', $instagram['id']) }}">Instagram</a></li>
                                    <li class="breadcrumb-item active">Post Insights</li>
                                </ol>
                            </nav>
                            <h2 class="mb-1">Post Insights</h2>
                            <p class="text-muted mb-0">Performance analytics for your Instagram post</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="{{ $postData['permalink'] }}" target="_blank" class="btn btn-primary btn-sm">
                                <i class="fab fa-instagram"></i> View Post
                            </a>
                            <a href="{{ route('instagram.show', $instagram['id']) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Post Preview -->
    <div class="row mb-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Post Preview</h5>
                </div>
                <div class="card-body text-center">
                    @if($postData['media_type'] === 'VIDEO')
                        <video controls style="max-width: 100%; border-radius: 8px;">
                            <source src="{{ $postData['media_url'] }}" type="video/mp4">
                        </video>
                    @else
                        <img src="{{ $postData['media_url'] }}" alt="Post" style="max-width: 100%; border-radius: 8px;">
                    @endif
                    
                    <div class="mt-3">
                        <p class="mb-2"><strong>Posted:</strong> {{ $postData['timestamp'] }}</p>
                        <p class="text-muted">{{ Str::limit($postData['caption'], 100) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="col-lg-8">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number">{{ number_format($postData['likes']) }}</div>
                        <div class="stat-label">Likes</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card" style="border-left-color: #10b981;">
                        <div class="stat-number" style="color: #10b981;">{{ number_format($postData['comments']) }}</div>
                        <div class="stat-label">Comments</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card" style="border-left-color: #f59e0b;">
                        <div class="stat-number" style="color: #f59e0b;">{{ number_format($postData['impressions']) }}</div>
                        <div class="stat-label">Impressions</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card" style="border-left-color: #ef4444;">
                        <div class="stat-number" style="color: #ef4444;">{{ $postData['engagement_rate'] }}%</div>
                        <div class="stat-label">Engagement Rate</div>
                    </div>
                </div>
            </div>

            <!-- Additional Stats -->
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="stat-card" style="border-left-color: #8b5cf6;">
                        <div class="stat-number" style="color: #8b5cf6;">{{ number_format($postData['reach']) }}</div>
                        <div class="stat-label">Reach</div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card" style="border-left-color: #06b6d4;">
                        <div class="stat-number" style="color: #06b6d4;">{{ number_format($postData['saves']) }}</div>
                        <div class="stat-label">Saves</div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card" style="border-left-color: #84cc16;">
                        <div class="stat-number" style="color: #84cc16;">{{ number_format($postData['total_engagement']) }}</div>
                        <div class="stat-label">Total Engagement</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Simple Graphs Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Performance Analytics</h5>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary active time-filter" data-range="week">
                                Last 7 Days
                            </button>
                            <button type="button" class="btn btn-outline-primary time-filter" data-range="month">
                                Last 30 Days
                            </button>
                        </div>
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
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
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

            // Performance Chart
            performanceChart = new ApexCharts(document.querySelector("#performance-chart"), {
                series: [
                    {
                        name: 'Impressions',
                        data: data.impressions
                    },
                    {
                        name: 'Engagement',
                        data: data.engagement
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

            // Engagement Chart
            engagementChart = new ApexCharts(document.querySelector("#engagement-chart"), {
                series: [{
                    name: 'Likes',
                    data: data.likes
                }],
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
                colors: ['#f59e0b']
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
@endpush