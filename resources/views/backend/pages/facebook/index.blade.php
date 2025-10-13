@extends('backend.pages.layouts.master')
@section('title','Facebook Integration - Windsource Style')
@push('styles')
<style>
    .permission-badge {
        font-size: 0.7rem;
        padding: 3px 8px;
        margin: 2px;
    }
    .stat-card {
        background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 15px;
    }
    .permission-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
        margin-top: 15px;
    }
</style>
@endpush

@section('main-content')
<div class="container-fluid">
    <!-- Success Message -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <strong>{{ session('success') }}</strong>
        @if(session('info'))
        <div class="mt-2">{{ session('info') }}</div>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Main Dashboard -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">🚀 Facebook Integration - Complete Access</h4>
                    <small class="opacity-75">Windsource Style Automated Setup</small>
                </div>
                <div class="card-body">
                    @if($mainAccount)
                    <div class="row">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-4">
                                @if($mainAccount->avatar)
                                <img src="{{ $mainAccount->avatar }}" class="rounded-circle me-3" width="60" height="60" alt="Profile">
                                @endif
                                <div>
                                    <h4 class="mb-1">{{ $mainAccount->account_name }}</h4>
                                    <p class="text-muted mb-0">{{ $mainAccount->account_email }}</p>
                                    <span class="badge bg-success">
                                        <i class="fas fa-bolt"></i>
                                        {{ ucfirst($mainAccount->permission_level) }} Access
                                    </span>
                                </div>
                            </div>

                            <!-- Permission Status -->
                            

                            <h5>🔑 Granted Permissions</h5>
                            <div class="permission-grid">
                                @foreach($permissions as $perm => $granted)
                                <div class="border rounded p-2 text-center">
                                    <div class="mb-1">
                                        @if($granted)
                                        <i class="fas fa-check-circle text-success"></i>
                                        @else
                                        <i class="fas fa-times-circle text-danger"></i>
                                        @endif
                                    </div>
                                    <small class="d-block">{{ str_replace('_', ' ', $perm) }}</small>
                                    <span class="badge {{ $granted ? 'bg-success' : 'bg-secondary' }} permission-badge">
                                        {{ $granted ? 'Granted' : 'Not Available' }}
                                    </span>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-center">
                                <a href="{{ route('social.disconnect', ['provider' => 'facebook']) }}" 
                                   class="btn btn-outline-danger btn-sm mb-3"
                                   onclick="return confirm('Disconnect Facebook?')">
                                    <i class="fas fa-unlink"></i> Disconnect
                                </a>
                                
                                <div class="border rounded p-3 bg-light">
                                    <h6>📊 Connection Status</h6>
                                    <div class="text-start small">
                                        <div class="d-flex justify-content-between">
                                            <span>Permission Level:</span>
                                            <strong class="text-primary">{{ $mainAccount->permission_level }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Connected Since:</span>
                                            <strong>{{ $mainAccount->created_at->format('M d, Y') }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Data Access:</span>
                                            <strong>{{ array_sum($permissions) }}/{{ count($permissions) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <!-- Connect Button -->
                    <div class="text-center py-4">
                        <div class="mb-4">
                            <i class="fas fa-rocket fa-4x text-primary mb-3"></i>
                            <h3>Complete Facebook Integration</h3>
                            <p class="text-muted">Get automatic access to posts, pages, ads, insights, and analytics</p>
                        </div>
                        
                        <a href="{{ route('social.redirect', ['provider' => 'facebook']) }}" 
                           class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-bolt"></i> Connect Facebook Account
                        </a>
                        
                        <div class="mt-4">
                            <h6>🎯 What you'll get automatically:</h6>
                            <div class="row mt-3">
                                <div class="col-md-6 text-start">
                                    <ul class="list-unstyled">
                                        <li>✅ Profile & Basic Information</li>
                                        <li>✅ Posts & Content Access</li>
                                        <li>✅ Photos & Videos</li>
                                        <li>✅ Pages Management</li>
                                    </ul>
                                </div>
                                <div class="col-md-6 text-start">
                                    <ul class="list-unstyled">
                                        <li>✅ Instagram Accounts</li>
                                        <li>✅ Ad Accounts & Analytics</li>
                                        <li>✅ Insights & Metrics</li>
                                        <li>✅ Audience Demographics</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($mainAccount)
    <!-- Analytics Dashboard -->
    <div class="row">
        <!-- Posts Analytics -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">📝 Posts Analytics</h5>
                </div>
                <div class="card-body">
                    @if(isset($analytics['posts']) && count($analytics['posts']) > 0)
                        @php
                            $totalLikes = 0;
                            $totalComments = 0;
                            $totalShares = 0;
                            foreach($analytics['posts'] as $post) {
                                $totalLikes += $post['likes']['summary']['total_count'] ?? 0;
                                $totalComments += $post['comments']['summary']['total_count'] ?? 0;
                                $totalShares += $post['shares']['count'] ?? 0;
                            }
                        @endphp
                        
                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <div class="stat-card">
                                    <h4>{{ $totalLikes }}</h4>
                                    <small>Total Likes</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-card">
                                    <h4>{{ $totalComments }}</h4>
                                    <small>Total Comments</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-card">
                                    <h4>{{ $totalShares }}</h4>
                                    <small>Total Shares</small>
                                </div>
                            </div>
                        </div>

                        <div style="max-height: 400px; overflow-y: auto;">
                            @foreach(array_slice($analytics['posts'], 0, 10) as $post)
                            <div class="border-bottom pb-2 mb-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <p class="mb-1 small">{{ Str::limit($post['message'] ?? 'No message', 100) }}</p>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($post['created_time'])->format('M d, Y') }}
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-primary">👍 {{ $post['likes']['summary']['total_count'] ?? 0 }}</span>
                                        <span class="badge bg-success">💬 {{ $post['comments']['summary']['total_count'] ?? 0 }}</span>
                                        <span class="badge bg-info">🔄 {{ $post['shares']['count'] ?? 0 }}</span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted text-center">No posts data available</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Connected Assets -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">🔗 Connected Assets</h5>
                </div>
                <div class="card-body">
                    <!-- Pages -->
                    @if($connectedPages->count() > 0)
                    <h6>📄 Facebook Pages ({{ $connectedPages->count() }})</h6>
                    <div class="mb-3">
                        @foreach($connectedPages as $page)
                        <div class="border rounded p-2 mb-2">
                            <strong>{{ $page->account_name }}</strong>
                            <br>
                            <small class="text-muted">ID: {{ $page->account_id }}</small>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    <!-- Instagram Accounts -->
                    @if($instagramAccounts->count() > 0)
                    <h6>📷 Instagram Accounts ({{ $instagramAccounts->count() }})</h6>
                    <div class="mb-3">
                        @foreach($instagramAccounts as $ig)
                        <div class="border rounded p-2 mb-2">
                            <strong>{{ $ig->account_name }}</strong>
                            <br>
                            <small class="text-muted">ID: {{ $ig->account_id }}</small>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    <!-- Ad Accounts -->
                    @if($adAccounts->count() > 0)
                    <h6>💰 Ad Accounts ({{ $adAccounts->count() }})</h6>
                    <div>
                        @foreach($adAccounts as $ad)
                        <div class="border rounded p-2 mb-2">
                            <strong>{{ $ad->ad_account_name }}</strong>
                            <br>
                            <small class="text-muted">
                                ID: {{ $ad->ad_account_id }} | 
                                Status: <span class="badge bg-{{ $ad->account_status == 'ACTIVE' ? 'success' : 'warning' }}">{{ $ad->account_status }}</span> |
                                Spent: ${{ number_format($ad->amount_spent, 2) }}
                            </small>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    @if($connectedPages->count() == 0 && $instagramAccounts->count() == 0 && $adAccounts->count() == 0)
                    <p class="text-muted text-center">No additional assets connected</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
// Auto-refresh analytics every 2 minutes
@if($mainAccount)
setInterval(function() {
    window.location.reload();
}, 120000);
@endif

// Permission test function
function testAllPermissions() {
    alert('🔍 Testing all permissions...\n\nThe system automatically detects and uses all available permissions.\nMissing permissions will be gracefully handled.');
}
</script>
@endpush