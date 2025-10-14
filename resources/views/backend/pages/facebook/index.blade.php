@extends('backend.pages.layouts.master')
@section('title', 'Facebook Integration')

@push('styles')
<style>
    .permission-badge {
        font-size: 0.75rem;
    }

    .profile-item img {
        border: 2px solid #ddd;
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

    <div class="row mb-4">
        <div class="col-12">
            @if($mainAccount)
            <div class="card">
                <div class="card-header text-white">
                    <h4 class="card-title mb-0">Facebook Integration - Connected</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="profile-item d-flex align-items-center mb-4">
                                    @if($mainAccount->avatar)
                                    <img src="{{ $mainAccount->avatar }}" class="rounded-circle me-3" width="60" height="60" alt="Profile">
                                    @endif
                                    <div>
                                        <h4 class="mb-1">{{ $mainAccount->account_name }}</h4>
                                        @if($mainAccount->account_email)
                                        <p class="text-muted mb-0">{{ $mainAccount->account_email }}</p>
                                        @endif
                                        <span class="badge bg-success mt-1">
                                            <i class="fas fa-bolt"></i> {{ ucfirst($mainAccount->permission_level) }} Access
                                        </span>
                                    </div>
                                </div>
                                <form action="{{ route('social.disconnect', ['provider' => 'facebook']) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                        onclick="return confirm('Disconnect Facebook?')">
                                        <i class="fas fa-unlink"></i> Disconnect
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><strong>Facebook Pages</strong></label>
                            <select class="form-select" id="facebookPagesDropdown">
                                <option value="">Select Page ({{ count($facebookData['pages'] ?? []) }} total)</option>
                                @if(!empty($facebookData['pages']))
                                @foreach($facebookData['pages'] as $page)
                                <option value="{{ $page['id'] }}">
                                    {{ $page['name'] }}
                                </option>
                                @endforeach
                                @else
                                <option disabled>No connected pages found</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><strong>Instagram Accounts</strong></label>
                            <select class="form-select" id="instagramDropdown">
                                <option value="">Select Instagram ({{ $stats['total_instagram_accounts'] }} total)</option>
                                @foreach($instagramAccounts as $ig)
                                    <option value="{{ $ig->id }}">
                                        {{ $ig->account_name }} ({{ number_format($ig->meta_data['followers_count'] ?? 0) }} followers)
                                    </option>
                                @endforeach
                            </select>

                        </div>
                        @if(!empty($permissions))
                        <div class="col-lg-12 mt-3">
                            <h5>Granted Permissions</h5>
                            <div class="row">
                                @foreach($permissions as $permission)
                                <div class="col-lg-3 mb-2">
                                    <div class="border rounded p-2 text-center">
                                        <div class="mb-1">
                                            @if($permission['status'] == 'granted')
                                            <i class="fas fa-check-circle text-success"></i>
                                            @else
                                            <i class="fas fa-times-circle text-danger"></i>
                                            @endif
                                        </div>
                                        <small class="d-block text-capitalize">{{ str_replace('_', ' ', $permission['permission']) }}</small>
                                        <span class="badge {{ $permission['status'] == 'granted' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $permission['status'] == 'granted' ? 'Granted' : 'Not Granted' }}
                                        </span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        @if(!empty($facebookData['pages']))
                        <div class="col-lg-12 mt-4">
                            <h5>Facebook Pages</h5>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($facebookData['pages'] as $page)
                                    <tr>
                                        <td>{{ $page['id'] }}</td>
                                        <td>{{ $page['name'] }}</td>
                                        <td>{{ $page['category'] ?? 'N/A' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                        @if(!empty($analytics['ad_accounts']))
                        <div class="col-lg-12 mt-4">
                            <h5>Ad Account</h5>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Account status</th>
                                        <th>Amount spent</th>
                                        <th>currency</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($analytics['ad_accounts'] as $adAcount)
                                    <tr>
                                        <td>{{ $adAcount['id'] }}</td>
                                        <td>{{ $adAcount['name'] }}</td>
                                        <td>{{ $adAcount['account_status'] ?? 'N/A' }}</td>
                                        <td>{{ $adAcount['amount_spent'] ?? 'N/A' }}</td>
                                        <td>{{ $adAcount['currency'] ?? 'N/A' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                        
                    </div>
                </div>
            </div>
            @else
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">Facebook Integration</h4>
                </div>
                <div class="card-body text-center py-5">
                    <i class="fas fa-rocket fa-4x text-primary mb-3"></i>
                    <h3>Connect Your Facebook Account</h3>
                    <p class="text-muted">Access pages, posts, insights, and analytics seamlessly.</p>

                    <a href="{{ route('social.redirect', ['provider' => 'facebook']) }}" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-bolt"></i> Connect Facebook Account
                    </a>

                    <div class="mt-4">
                        <h6>What you'll get:</h6>
                        <div class="row mt-3">
                            <div class="col-md-6 text-start">
                                <ul class="list-unstyled">
                                    <li>✅ Profile & Basic Info</li>
                                    <li>✅ Pages & Posts</li>
                                    <li>✅ Photos & Videos</li>
                                    <li>✅ Ad Accounts</li>
                                </ul>
                            </div>
                            <div class="col-md-6 text-start">
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
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if($mainAccount)
<script>
    document.getElementById('instagramDropdown').addEventListener('change', function() {
        var igId = this.value;
        if(igId) {
            let url = "{{ route('instagram.show', ['id' => '__ID__']) }}";
            url = url.replace('__ID__', igId);
            window.location.href = url;
        }
    });
</script>
<!-- <script>
        setInterval(() => location.reload(), 120000);
</script> -->
@endif
@endpush