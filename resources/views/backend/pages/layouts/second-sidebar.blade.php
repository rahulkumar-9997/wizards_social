<div class="col-xxl-3 mb-sm-1 mb-md-1 mb-lg-2 mb-xl-0 pe-xl-0 ps-xl-2">
    <div class="offcanvas-xxl offcanvas-start sidebar-menu-second">
        <div class="card h-100 mb-0">
            <div class="card-body">
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
                            {{ isset($selectedFbId) && $selectedFbId == $page['id'] ? 'selected' : '' }} 
                            >
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
                            <i class="bx bxs-inbox fs-16 me-2 align-middle"></i> Ads
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>