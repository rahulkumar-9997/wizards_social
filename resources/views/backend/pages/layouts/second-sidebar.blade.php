<div class="col-xxl-3 mb-sm-1 mb-md-1 mb-lg-2 mb-xl-0 pe-xl-0 ps-xl-2" id="leftSidebar">
    <div class="offcanvas-xxl offcanvas-start sidebar-menu-second rp-reports">
        <div class="card h-100 mb-0">
            <div class="card-body">
                <div class="d-flex flex-column select-accounts accounts-filter h-100 pt-3 pb-3" id="socialAccountsCollapse">
                    <div class="d-flex justify-content-between mb-3 align-items-center w-100">
                        <p class="title b4 fw-semibold w-100 m-0">Social Profiles</p>
                        <button class="btn btn-secondary btn-icon library-expand" id="toggleSidebar">
                            <span class="icon-collaps"></span>
                        </button>
                    </div>
                    <div class="account-list pb-3 w-100">
                        <a href="https://social.recurpost.com/reports" class="filtersmpalink inner-list-box">
                            <div class="d-flex align-items-center social-account rounded p-1 selected">
                                <div class="form-check-label d-flex align-items-center w-100">
                                    <div class="schedule-pic-box">
                                        <img src="https://social.recurpost.com/addon/img/all-user.png" class="schedule-pic rounded-circle img-fluid">
                                    </div>
                                    <div class="user-info overflow-hidden ps-2">
                                        <p class="m-0">Overview</p>
                                        <p class="m-0 b6">All social profiles</p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="input-group mb-3 search-account d-none">
                        <input type="text" id="search_post_smpa" class="form-control border-end-0" placeholder="Search">
                        <button class="btn btn-light border border-start-0 bg-transparent" type="button"><span class="icon-search"></span></button>
                    </div>

                    <div class="account-list overflow-y h-100 w-100 pb-3" id="account_list">
                        @if(!empty($fbInstagram) && $fbInstagram->isNotEmpty())
                            @foreach($fbInstagram as $ig)
                                <a href="{{ route('instagram.show', $ig['id']) }}">
                                    <div class="d-flex align-items-center social-account rounded p-1 position-relative " data-bs-toggle="tooltip" data-bs-original-title="{{ $ig['account_name'] }}">                                    
                                        <div class="form-check-label d-flex align-items-center w-100 filtersmpalink">
                                            <div class="schedule-pic-box ">
                                                <img src="{{ $ig['profile_picture'] }}" class="schedule-pic rounded-circle img-fluid">
                                                <img class="small-social-icon" src="https://social.recurpost.com/addon/img/Instagram.svg">
                                            </div>
                                            <div class="user-info overflow-hidden ps-2">
                                                <p class="m-0 account-name">
                                                    {{ $ig['account_name'] }} — {{ number_format($ig['followers_count'] ?? 0) }} followers
                                                </p>
                                                <p class="m-0 b6">Instagram Business Profile</p>
                                            </div>
                                        </div>
                                        <i class="user-check position-absolute align-center rounded-circle ri-checkbox-circle-line b3"></i>
                                    </div> 
                                </a>
                            @endforeach
                         @else
                            <div class="text-center text-muted no-acc-div d-none">
                                No Social profile found.
                            </div>
                        @endif                       
                    </div>
                </div>
                <div class="nav flex-column">
                    @if(!empty($fbInstagram) && $fbInstagram->isNotEmpty())
                    <div class="mt-2">
                        <label for="instagram_accounts" class="form-label fw-semibold">Select Instagram Account *</label>
                        <select class="form-control" id="instagram_accounts"
                            name="instagram_account_id"
                            data-choices data-placeholder="Select Instagram Account" required>
                            <option value="">Choose Instagram Account</option>
                            @foreach($fbInstagram as $ig)
                            <option
                                value="{{ $ig['id'] }}"
                                {{ isset($selectedInstagramId) && $selectedInstagramId == $ig['id'] ? 'selected' : '' }}>
                                {{ $ig['account_name'] }} — {{ number_format($ig['followers_count'] ?? 0) }} followers
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    <div class="text-muted small mt-2">No Instagram accounts connected.</div>
                    @endif

                    {{-- ================= Facebook Page Selector ================= --}}
                    @if(!empty($fbPages))
                    <div class="mt-2 mb-2">
                        <label for="facebook_pages" class="form-label fw-semibold">Select Facebook Page *</label>
                        <select class="form-control" id="facebook_pages"
                            name="facebook_page_id"
                            data-choices data-placeholder="Select Facebook Page" required>
                            <option value="">Choose Facebook Page</option>
                            @foreach ($fbPages as $page)
                            @php
                            $hasInstagram = $fbInstagram
                            ->where('connected_page', $page['name'])
                            ->count() > 0;
                            @endphp
                            <option value="{{ $page['id'] }}"
                                {{ isset($selectedFbId) && $selectedFbId == $page['id'] ? 'selected' : '' }}>
                                {{ $page['name'] }}
                                @if(!empty($page['category']))
                                ({{ $page['category'] }})
                                @endif
                                — {{ $hasInstagram ? '✅ Instagram Connected' : '❌ No Instagram' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    <div class="text-muted small mt-2">No Facebook pages connected.</div>
                    @endif
                    <a class="nav-link px-0 py-1 active" href="{{ route('face.ads') }}">
                        <span class="text-danger fw-bold">
                            <i class="bx bxs-inbox fs-16 me-2 align-middle"></i>Meta Ads
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>