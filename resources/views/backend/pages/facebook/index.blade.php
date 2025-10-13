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

    @if($mainAccount && $connectedPages->count() > 0)
    <!-- All Facebook Pages Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        📄 All Facebook Pages ({{ $connectedPages->count() }} Pages Found)
                        <small class="float-end">Automatically Connected from Your Facebook Account</small>
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Pages Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded bg-light">
                                <h3 class="text-primary mb-1">{{ $connectedPages->count() }}</h3>
                                <small class="text-muted">Total Pages</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded bg-light">
                                <h3 class="text-success mb-1">{{ number_format($stats['total_page_fans']) }}</h3>
                                <small class="text-muted">Total Page Fans</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded bg-light">
                                <h3 class="text-instagram mb-1">{{ $stats['pages_with_instagram'] }}</h3>
                                <small class="text-muted">Pages with Instagram</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded bg-light">
                                <h3 class="text-info mb-1">{{ $stats['total_instagram_accounts'] }}</h3>
                                <small class="text-muted">Instagram Accounts</small>
                            </div>
                        </div>
                    </div>

                    <!-- Pages Grid -->
                    <div class="row">
                        @foreach($connectedPages as $page)
                        @php
                        $pageMeta = json_decode($page->meta_data, true) ?? [];
                        $hasInstagram = $instagramAccounts->where('parent_account_id', $page->id)->count() > 0;
                        $instagramAccount = $hasInstagram ? $instagramAccounts->where('parent_account_id', $page->id)->first() : null;
                        $igMeta = $instagramAccount ? json_decode($instagramAccount->meta_data, true) : [];
                        @endphp
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 text-truncate">{{ $page->account_name }}</h6>
                                    @if($pageMeta['verified'] ?? false)
                                    <span class="badge bg-success" title="Verified Page">✓</span>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <!-- Page Info -->
                                    <div class="mb-3">
                                        <small class="text-muted d-block">
                                            <strong>Category:</strong> {{ $pageMeta['category'] ?? 'Unknown' }}
                                        </small>
                                        <small class="text-muted d-block">
                                            <strong>Fans:</strong> {{ number_format($pageMeta['fan_count'] ?? 0) }}
                                        </small>
                                        @if($pageMeta['username'] ?? false)
                                        <small class="text-muted d-block">
                                            <strong>Username:</strong> {{ $pageMeta['username'] }}
                                        </small>
                                        @endif
                                    </div>

                                    <!-- Instagram Connection -->
                                    @if($hasInstagram && $instagramAccount)
                                    <div class="border-top pt-2">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fab fa-instagram text-instagram me-2"></i>
                                            <small class="text-muted"><strong>Connected Instagram:</strong></small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            @if($igMeta['profile_picture_url'] ?? false)
                                            <img src="{{ $igMeta['profile_picture_url'] }}" class="rounded-circle me-2" width="30" height="30" alt="Instagram">
                                            @endif
                                            <div class="flex-grow-1">
                                                <small class="d-block"><strong>{{ $igMeta['username'] ?? 'N/A' }}</strong></small>
                                                <small class="text-muted">
                                                    {{ number_format($igMeta['followers_count'] ?? 0) }} followers •
                                                    {{ number_format($igMeta['media_count'] ?? 0) }} posts
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    @else
                                    <div class="border-top pt-2">
                                        <small class="text-muted"><i>No Instagram connected</i></small>
                                    </div>
                                    @endif
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between">
                                        <a href="{{ $pageMeta['link'] ?? '#' }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-external-link-alt"></i> Visit
                                        </a>
                                        @if($hasInstagram)
                                        <span class="badge bg-instagram align-self-center">
                                            <i class="fab fa-instagram"></i> Connected
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
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