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

    @if($mainAccount)
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <div>
                        <h4 class="mb-1">Facebook Integration</h4>
                        <p class="text-muted mb-0">Manage your Facebook pages and connected accounts</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @php
                        $tokenStatus = 'valid';
                        $tokenMessage = 'Token Valid';
                        $badgeClass = 'bg-success';
                        $iconClass = 'fa-check-circle';

                        if($mainAccount->isTokenExpiringSoon()) {
                        $tokenStatus = 'expiring_soon';
                        $tokenMessage = 'Expiring Soon';
                        $badgeClass = 'bg-warning';
                        $iconClass = 'fa-clock';
                        } elseif($mainAccount->isTokenExpired()) {
                        $tokenStatus = 'expired';
                        $tokenMessage = 'Token Expired';
                        $badgeClass = 'bg-danger';
                        $iconClass = 'fa-exclamation-triangle';
                        }
                        @endphp

                        <div class="text-end me-3">
                            <span class="badge {{ $badgeClass }} token-status-badge d-flex align-items-center">
                                <i class="fas {{ $iconClass }} me-1"></i>
                                {{ $tokenMessage }}
                            </span>
                            @if($mainAccount->token_expires_at)
                            <small class="text-muted d-block mt-1">
                                Expires: {{ $mainAccount->token_expires_at->format('M j, Y') }}
                            </small>
                            @endif
                        </div>
                        <a href="{{ route('facebook.refresh.token') }}" class="btn btn-warning btn-sm"
                            onclick="return confirm('Refresh Facebook token? This will update your access token.')">
                            <i class="fas fa-sync-alt"></i> Refresh Token
                        </a>

                        <form action="{{ route('social.disconnect', ['provider' => 'facebook']) }}" method="POST" class="m-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm"
                                onclick="return confirm('Are you sure you want to disconnect Facebook? This will remove all connected data.')">
                                <i class="fas fa-unlink"></i> Disconnect
                            </button>
                        </form>
                    </div>
                    @if($tokenStatus !== 'valid')
                    <div class="alert alert-{{ $tokenStatus === 'expired' ? 'danger' : 'warning' }} alert-dismissible fade show mb-3" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas {{ $iconClass }} me-2"></i>
                            <div>
                                <strong>Facebook Token {{ ucfirst(str_replace('_', ' ', $tokenStatus)) }}!</strong>
                                @if($tokenStatus === 'expired')
                                Your Facebook access token has expired. Please refresh the token to continue accessing your data.
                                @elseif($tokenStatus === 'expiring_soon')
                                Your Facebook token will expire soon. It's recommended to refresh it to avoid interruption.
                                @endif
                                @if($mainAccount->token_expires_at)
                                <br><small>Expiration: {{ $mainAccount->token_expires_at->format('F j, Y \a\t g:i A') }}
                                    ({{ $mainAccount->token_expires_at->diffForHumans() }})</small>
                                @endif
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif                    
                </div>
                <div class="card-body">
                    <div class="profile-item d-flex align-items-center mb-2 p-3 bg-light rounded">
                        @if(isset($dashboardData['profile']['picture']['data']['url']))
                        <img src="{{ $dashboardData['profile']['picture']['data']['url'] }}"
                            class="rounded-circle me-3" width="80" height="80" alt="Profile">
                        @endif
                        <div class="flex-grow-1">
                            <h4 class="mb-1">{{ $dashboardData['profile']['name'] ?? 'Unknown' }}</h4>
                            @if(isset($dashboardData['profile']['email']))
                            <p class="text-muted mb-1">{{ $dashboardData['profile']['email'] }}</p>
                            @endif
                            <span class="badge bg-success">
                                <i class="fas fa-bolt"></i> Connected
                            </span>
                            @if($mainAccount->token_expires_at)
                            <span class="badge bg-{{ $tokenStatus === 'expired' ? 'danger' : ($tokenStatus === 'expiring_soon' ? 'warning' : 'success') }} ms-2">
                                <i class="fas {{ $iconClass }}"></i>
                                Token {{ $mainAccount->token_expires_at->diffForHumans() }}
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="quick-stats">
                        <div class="stat-card card text-center">
                            <div class="card-body">
                                <h3 class="text-primary">{{ $stats['total_pages'] }}</h3>
                                <p class="text-muted mb-0">Facebook Pages</p>
                            </div>
                        </div>
                        <div class="stat-card card text-center">
                            <div class="card-body">
                                <h3 class="text-success">{{ $stats['total_instagram_accounts'] }}</h3>
                                <p class="text-muted mb-0">Instagram Accounts</p>
                            </div>
                        </div>
                        <div class="stat-card card text-center">
                            <div class="card-body">
                                <h3 class="text-info">{{ number_format($stats['total_instagram_followers']) }}</h3>
                                <p class="text-muted mb-0">Total Followers</p>
                            </div>
                        </div>
                        <div class="stat-card card text-center">
                            <div class="card-body">
                                <h3 class="text-warning">{{ $stats['total_ad_accounts'] }}</h3>
                                <p class="text-muted mb-0">Ad Accounts</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-2">
                                <label for="facebook_pages" class="form-label">Select Facebook Page *</label>
                                <select class="form-control" id="facebook_pages"
                                    data-choices data-placeholder="Select Facebook Page"
                                    name="facebook_pages" required>
                                    <option value="">Choose a Facebook Page</option>

                                    @if (!empty($dashboardData['pages']))
                                    @foreach ($dashboardData['pages'] as $page)
                                    @php
                                    $hasInstagram = collect($dashboardData['instagram_accounts'])
                                    ->where('connected_page', $page['name'])
                                    ->count() > 0;
                                    @endphp
                                    <option value="{{ $page['id'] }}">
                                        {{ $page['name'] }}
                                        @if(!empty($page['category']))
                                        ({{ $page['category'] }})
                                        @endif
                                        — {{ $hasInstagram ? '✅ Instagram Connected' : '❌ No Instagram' }}
                                    </option>
                                    @endforeach
                                    @else
                                    <option disabled>No Facebook pages found.</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-2">
                                <label for="instagram_pages" class="form-label">Select Instagram Account *</label>
                                <select class="form-control" id="instagram_pages"
                                    data-choices data-placeholder="Select Instagram Account"
                                    name="instagram_pages" required>
                                    <option value="">Choose Instagram Account</option>

                                    @if (!empty($dashboardData['instagram_accounts']) && $dashboardData['instagram_accounts']->count() > 0)
                                    @foreach ($dashboardData['instagram_accounts'] as $ig)
                                    <option value="{{ $ig['id'] }}">
                                        {{ $ig['account_name'] }}
                                        ({{ $ig['username'] }})
                                        — {{ number_format($ig['followers_count'] ?? 0) }} followers
                                    </option>
                                    @endforeach
                                    @else
                                    <option disabled>No Instagram accounts connected.</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>       

    </div>
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="dashboard-section h-100">
                <h5 class="mb-3">
                    <i class="fas fa-shield-alt text-warning"></i>
                    Permissions ({{ $stats['total_permissions_granted'] }}/{{ count($dashboardData['permissions'] ?? []) }})
                </h5>
                <div class="row">
                    @foreach($dashboardData['permissions'] as $permission)
                    <div class="col-6 mb-2">
                        <div class="border rounded p-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-capitalize">{{ $permission['permission'] }}</small>
                                <span class="badge {{ $permission['status'] == 'granted' ? 'bg-success' : 'bg-secondary' }} permission-badge">
                                    {{ $permission['status'] == 'granted' ? '✓' : '✗' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Ad Accounts -->
        <div class="col-lg-6 mb-4">
            <div class="dashboard-section h-100">
                <h5 class="mb-3">
                    <i class="fas fa-ad text-info"></i>
                    Ad Accounts ({{ $stats['total_ad_accounts'] }})
                </h5>
                @if(!empty($dashboardData['analytics']['ad_accounts']))
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Spent</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dashboardData['analytics']['ad_accounts'] as $adAccount)
                            <tr>
                                <td>
                                    <small>{{ $adAccount['name'] }}</small>
                                    <br><small class="text-muted">ID: {{ $adAccount['id'] }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $adAccount['account_status'] == 1 ? 'success' : 'warning' }}">
                                        {{ $adAccount['account_status'] == 1 ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <small>
                                        {{ $adAccount['amount_spent'] ?? '0' }}
                                        {{ $adAccount['currency'] ?? 'USD' }}
                                    </small>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted">No ad accounts found.</p>
                @endif
            </div>
        </div>
    </div>

    @else
    <!-- Connect Facebook Card -->
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
                        class="btn btn-primary btn-lg px-5">
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
        const cards = document.querySelectorAll('.stat-card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
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
$(document).ready(function() {
    $('#instagram_pages').on('change', function() {
        let selectedId = $(this).val();
        if (selectedId) {
            let baseUrl = "{{ url('/instagram') }}"; 
            window.location.href = `${baseUrl}/${selectedId}`;
        }
    });
});
</script>
@endif
@endpush