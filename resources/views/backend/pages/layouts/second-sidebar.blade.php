<div class="col-xxl-3" id="leftSidebar">
    <div class="sidebar-menu-second rp-reports select-accounts">
        <div class="card h-100 mb-0">
            <div class="card-body d-flex flex-column">
                <div class="sidebar-header">
                    <div class="d-flex justify-content-between mb-1 align-items-center">
                        <h4 class="title b4 m-0">Social Profiles</h4>
                        <button class="btn btn-secondary btn-icon library-expand" id="toggleSidebar">
                            <iconify-icon icon="solar:double-alt-arrow-left-bold-duotone"></iconify-icon>
                        </button>
                    </div>
                    <!-- <div class="account-list pb-2">
                        <a href="#" class="filtersmpalink inner-list-box">
                            <div class="d-flex align-items-center social-account rounded p-1 selected">
                                <div class="schedule-pic-box">
                                    <img src="https://social.recurpost.com/addon/img/all-user.png"
                                        class="schedule-pic rounded-circle img-fluid">
                                </div>
                                <div class="user-info ps-2">
                                    <p class="m-0">Overview</p>
                                    <p class="m-0 b6">All social profiles</p>
                                </div>
                            </div>
                        </a>
                    </div> -->
                    <div class="search-sticky">
                        <div class="input-group search-account mb-2">
                            <input type="text" id="search_post_smpa"
                                class="form-control border-end-0"
                                placeholder="Search profiles">
                            <button class="btn btn-light border border-start-0 bg-transparent">
                                <iconify-icon icon="solar:magnifer-linear"></iconify-icon>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex-grow-1 px-0 main-scroll d-flex flex-column account-list">
                    <div class="social-sidebar">
                        @if(!empty($fbInstagram) && $fbInstagram->isNotEmpty())
                        <div class="instagram-list-container">
                            <h5 class="list-title">Instagram Pages ({{ count($fbInstagram) }})</h5>
                            <div class="instagram-list">
                                @foreach($fbInstagram as $ig)
                                <a href="{{ route('instagram.show', $ig['id']) }}" class="account-item">
                                    <div class="d-flex align-items-center social-account rounded p-1 position-relative {{ isset($selectedInstagramId) && $selectedInstagramId == $ig['id'] ? 'selected' : '' }}"
                                     data-bs-toggle="tooltip"
                                        data-bs-original-title="{{ $ig['account_name'] }}">
                                        <div class="form-check-label d-flex align-items-center w-100 filtersmpalink">
                                            <div class="schedule-pic-box">
                                                <img src="{{ $ig['profile_picture'] }}"
                                                    class="schedule-pic rounded-circle img-fluid">
                                                <img class="small-social-icon"
                                                    src="{{ asset('backend/assets/Instagram.svg') }}">
                                            </div>
                                            <div class="user-info overflow-hidden ps-2">
                                                <p class="m-0 account-name">
                                                    {{ $ig['account_name'] }}
                                                    — {{ number_format($ig['followers_count'] ?? 0) }} followers
                                                </p>
                                                <p class="m-0 b6">Instagram Business Profile</p>
                                            </div>
                                        </div>
                                        <iconify-icon icon="solar:check-circle-line-duotone" class="user-check position-absolute align-center rounded-circle ri-checkbox-circle-line b3"></iconify-icon>
                                    </div>                                    
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        @if(!empty($fbPages))
                        <div class="facebook-list-container mt-3">
                            <h5 class="list-title">Facebook Pages ({{ count($fbPages) }})</h5>
                            <div class="facebook-list">
                                @foreach ($fbPages as $page)
                                @php
                                $hasInstagram = $fbInstagram
                                ->where('connected_page', $page['name'])
                                ->count() > 0;
                                @endphp
                                <a href="{{ route('facebook.report', $page['id']) }}" class="account-item">
                                    <div class="d-flex align-items-center social-account rounded p-1 position-relative {{ isset($selectedFbId) && $selectedFbId == $page['id'] ? 'selected' : '' }} "
                                    data-bs-toggle="tooltip"
                                        data-bs-original-title="{{ $page['name'] }}">
                                        <div class="form-check-label d-flex align-items-center w-100 ">
                                            <div class="schedule-pic-box">
                                                <img src="{{ $page['profile_picture'] }}"
                                                    class="schedule-pic rounded-circle img-fluid">
                                                <img class="small-social-icon"
                                                    src="{{ asset('backend/assets/facebook_icon.svg.png') }}">
                                            </div>
                                            <div class="user-info overflow-hidden ps-2">
                                                <p class="m-0 account-name">
                                                    {{ $page['name'] }}
                                                    @if(!empty($page['category']))
                                                    ({{ $page['category'] }})
                                                    @endif
                                                    — {{ $hasInstagram ? '✅ Instagram Connected' : '❌ No Instagram' }}
                                                </p>
                                                <p class="m-0 b6">Facebook Business Profile</p>
                                            </div>
                                        </div>
                                        <iconify-icon icon="solar:check-circle-line-duotone" class="user-check position-absolute align-center rounded-circle ri-checkbox-circle-line b3"></iconify-icon>
                                    </div>
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        <div class="sidebar-footer" style="flex-shrink:0;">
                            <a class="nav-link px-0 py-1" href="{{ route('face.ads') }}">
                                <span class="text-danger fw-bold">
                                    <i class="bx bxs-inbox me-2"></i>Meta Ads
                                </span>
                            </a>
                        </div>                        
                    </div>                    
                </div>                
            </div>
        </div>
    </div>
</div>