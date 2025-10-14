@extends('backend.pages.layouts.master')
@section('title', 'Instagram Dashboard')

@push('styles')
<style>
    .profile-item img {
        border: 2px solid #ddd;
    }
    .stat-card {
        background: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        text-align: center;
        padding: 1rem;
        transition: all 0.3s ease;
    }
    .stat-card:hover {
        background: #f8f9fa;
    }
    .stat-card h3 {
        font-size: 1.8rem;
        margin: 0;
        color: #0d6efd;
    }
    .stat-card p {
        margin: 0;
        color: #6c757d;
        font-weight: 500;
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
                        <p class="text-muted mb-1">@{{ $instagram['username'] ?? '' }}</p>
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
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="avatar-md bg-danger rounded">
                                <i class="bx bxs-video-plus avatar-title fs-24 text-white"></i>
                            </div>
                        </div>
                        <div class="col-6 text-end">
                            <p class="text-muted mb-0 text-truncate">Real Posts</p>
                            <h3 class="text-dark mt-1 mb-0">
                                {{ $totalReels }}
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
                            <div class="avatar-md bg-warning rounded">
                                <i class="bx bxs-heart avatar-title fs-24 text-white"></i>
                            </div>
                        </div>
                        <div class="col-6 text-end">
                            <p class="text-muted mb-0 text-truncate">Total Likes</p>
                            <h3 class="text-dark mt-1 mb-0">
                                {{ $totalLikes }}
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-xxl-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Instagram Daily Insights</h4>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-light active filter-btn" data-filter="all">ALL</button>
                            <button type="button" class="btn btn-sm btn-outline-light" data-filter="1M">1M</button>
                            <button type="button" class="btn btn-sm btn-outline-light" data-filter="6M">6M</button>
                            <button type="button" class="btn btn-sm btn-outline-light" data-filter="1Y">1Y</button>
                        </div>
                    </div>

                    <div id="likes_graph" style="min-height: 350px;"></div>
                </div>
            </div>

    </div>
    <div class="row">
        <div class="col-12 mb-3">
            <h5 class="mb-3">Recent Posts</h5>
        </div>
        @forelse($media as $post)
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                @if(isset($post['media_url']))
                <img src="{{ $post['media_url'] }}" class="card-img-top" alt="Media">
                @endif
                <div class="card-body">
                    <p>{{ \Illuminate\Support\Str::limit($post['caption'] ?? '', 100) }}</p>
                    <p class="mb-2">
                        <strong>❤️ {{ $post['like_count'] ?? 0 }}</strong> &nbsp;
                        <strong>💬 {{ $post['comments_count'] ?? 0 }}</strong>
                    </p>
                    <a href="{{ $post['permalink'] }}" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                        View on Instagram
                    </a>
                </div>
            </div>
        </div>
        @empty
        <p class="text-center">No posts found.</p>
        @endforelse
    </div>

</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var chart;

    function loadGraph(range = 'all') {
        $("#likes_graph").html('<div class="text-center p-5 text-muted">Loading graph...</div>');

        $.ajax({
            url: "{{ route('instagram.likes.graph', ['id' => $instagram['id'] ?? 0]) }}",
            type: "GET",
            data: { range: range },
            dataType: "json",
            success: function(data) {
                if(chart) chart.destroy();

                var options = {
                    series: [
                        { name: "Likes", type: "area", data: data.likes },
                        { name: "Comments", type: "line", data: data.comments },
                        { name: "Views", type: "line", data: data.views }
                    ],
                    chart: { height: 350, type: "line", toolbar: { show: false } },
                    stroke: { width: [2,2,2], curve: 'smooth' },
                    colors: ["#22c55e","#0d6efd","#f59e0b"],
                    fill: { type: ['gradient','solid','solid'], gradient: { opacityFrom:0.5, opacityTo:0.1 } },
                    xaxis: { categories: data.dates },
                    legend: { show: true, position: 'top' },
                    tooltip: {
                        shared: true,
                        y: [
                            { formatter: y => y ? y + " Likes" : y },
                            { formatter: y => y ? y + " Comments" : y },
                            { formatter: y => y ? y + " Views" : y },
                        ]
                    }
                };

                chart = new ApexCharts(document.querySelector("#likes_graph"), options);
                chart.render();
            },
            error: function(xhr,status,error) {
                $("#likes_graph").html('<div class="text-center p-5 text-danger">Error loading graph</div>');
                console.error("Error fetching Instagram stats:", error);
            }
        });
    }

    $(".filter-btn").on("click", function() {
        var range = $(this).data("filter");
        $(".filter-btn").removeClass("active");
        $(this).addClass("active");
        loadGraph(range);
    });

    $(".filter-btn[data-filter='all']").trigger("click");
});
</script>
@endpush
