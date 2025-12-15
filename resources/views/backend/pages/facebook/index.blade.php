@extends('backend.pages.layouts.master')
@section('title', 'Facebook Integration')
@push('styles')
<style>
    .stat-card {
        transition: transform 0.2s;
        border: none;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .permission-badge {
        font-size: 0.75rem;
    }

    .profile-item img {
        border: 2px solid #ddd;
    }

    .quick-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 5px;
    }

    .token-status-badge {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
    }
</style>
@endpush

@section('main-content')
<div class="container-fluid">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <strong>{{ session('success') }}</strong>
        @if(session('info'))
        <div class="mt-2">{{ session('info') }}</div>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle"></i> <strong>{{ session('error') }}</strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($mainAccount && $checkRefreshToken)
    <div class="row">
        @include('backend.pages.layouts.second-sidebar')
        <div class="col-xl-9" id="mainContent">
            <div class="load-data-using-ajax">
                <div id="fb-dashboard-loader" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading Facebook data...</p>
                </div>
                <div id="fb-dashboard-content" style="display:none;"></div>
            </div>
        </div>
    </div>
    @else
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header text-white">
                    <h4 class="card-title mb-0">Facebook and Instagram Integration</h4>
                </div>
                <div class="card-body text-center py-5">
                    <i class="fas fa-rocket fa-4x text-primary mb-3"></i>
                    <h3>Connect Your Facebook Account</h3>
                    <p class="text-muted">Access pages, posts, insights, and analytics seamlessly.</p>

                    <a href="{{ route('social.redirect', ['provider' => 'facebook']) }}"
                        class="btn btn-primary px-5">
                        <i class="fas fa-bolt"></i> Connect Facebook Account
                    </a>

                    <div class="mt-4">
                        <h6>What you'll get:</h6>
                        <div class="row mt-3 justify-content-center">
                            <div class="col-md-5 text-start">
                                <ul class="list-unstyled">
                                    <li>✅ Profile & Basic Info</li>
                                    <li>✅ Pages & Posts</li>
                                    <li>✅ Photos & Videos</li>
                                    <li>✅ Ad Accounts</li>
                                </ul>
                            </div>
                            <div class="col-md-5 text-start">
                                <ul class="list-unstyled">
                                    <li>✅ Instagram Integration</li>
                                    <li>✅ Insights & Analytics</li>
                                    <li>✅ Audience Metrics</li>
                                    <li>✅ Auto Sync</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
@if($mainAccount)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if($mainAccount->isTokenExpired())
        setTimeout(() => {
            if (confirm('Your Facebook token has expired. Would you like to refresh it now?')) {
                window.location.href = "{{ route('facebook.refresh.token') }}";
            }
        }, 3000);
        @endif
    });
</script>
<script>
    const FB_DASHBOARD_URL = "{{ route('facebook.user.profile') }}";
    window.facebook_base_url = "{{ url('facebook-summary') }}";
    const INSTAGRAM_BASE_URL = "{{ url('/instagram') }}";
    window.INSTAGRAM_BASE_URL = INSTAGRAM_BASE_URL;
</script>
<script src="{{ asset('backend/assets/js/pages/fb-user-profile.js') }}"></script>
@endif
@endpush