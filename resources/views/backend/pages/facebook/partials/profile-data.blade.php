<div class="profile-data">
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
            <!-- @if($tokenStatus !== 'valid')
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
            @endif -->
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
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header text-white">
                    <h4 class="card-title mb-0">
                        Permissions ({{ $stats['total_permissions_granted'] }}/{{ count($dashboardData['permissions'] ?? []) }})
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($dashboardData['permissions'] as $permission)
                        <div class="col-lg-6">
                            <div class="mb-2">
                                <div class="border rounded p-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-capitalize">{{ $permission['permission'] }}</small>
                                        <span class="badge {{ $permission['status'] == 'granted' ? 'bg-success' : 'bg-secondary' }} permission-badge">
                                            {{ $permission['status'] == 'granted' ? '✓' : '✗' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Ad Accounts -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header text-white">
                    <h4 class="card-title mb-0">
                        Ad Accounts ({{ $stats['total_ad_accounts'] }})
                    </h4>
                </div>
                <div class="card-body">
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
    </div>
</div>