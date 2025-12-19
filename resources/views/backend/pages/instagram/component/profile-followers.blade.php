<div class="row">
    <div class="col-12">
        <h3 class="mandate-title">
            FOLLOWERS
        </h3>
        <p>
            It shows how many different people discovered your profile, not how many times it was
            viewed. Profile reach helps you understand your brand visibility and audience interest. Higher
            profile reach often means your content is attracting new users to your page.
        </p>
    </div>
    <div class="col-md-6 mb-sm-1 mb-md-1 mb-lg-5 mb-xl-0 col-6 pe-xl-0 ps-xl-0">
        <div class="mandate-section">
            <div class="mandate-item">
                <div class="mandate-item-header" style="box-shadow:  0 15px 13px -7px rgba(0, 0, 0, 0.2); padding: 10px; margin-bottom: 10px;">
                    <div class="text-center">
                        <div class="mandate-item-title">
                            <div class="mandate-item-text">
                                <h2 class="mb-0">
                                    FOLLOWERS
                                    @php
                                        $tooltipText = trim($followersData['follows']['api_description'] ?? '');
                                    @endphp
                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;"
                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                        data-bs-custom-class="success-tooltip"
                                        data-bs-title="{{ $tooltipText !== '' ? $tooltipText : 'No description available' }}">
                                    </i>
                                </h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mandate-item-body">
                    <div class="mandate-followers-body">
                        <div class="mandate-item-title d-flex justify-content-between align-items-center gap-1">
                            <div class="col-custom-3">
                                <h5 style="margin-bottom: 0px;">Previous Month</h5>
                                <h3 class="follow-font">
                                    {{ compact_number($followersData['follows']['previous'] ?? 0) }}
                                </h3>
                            </div>
                            <div class="col-custom-3">
                                <h5 style="margin-bottom: 0px;">Current Month</h5>
                                <h3 class="follow-font">
                                    {{ compact_number($followersData['follows']['current'] ?? 0) }}
                                </h3>
                            </div>
                            <div class="col-custom-3">
                                <div class="mandate-item-arrow">
                                    @php
                                    $followPercent = $followersData['follows']['percent_change'] ?? 0;
                                    $followIsPositive = $followPercent >= 0;
                                    $followColor = $followIsPositive ? '#28a745' : '#e70000ff';
                                    $followArrow = $followIsPositive ? 'green-arrow-up.png' : 'red-arrow-down.png';
                                    @endphp
                                    <h4 style="margin-bottom: 5px; color: {{ $followColor }}; font-size: 24px;">
                                        {{ abs($followPercent) }}%
                                    </h4>
                                    <div class="mandate-arrow-icon">
                                        <img src="{{ asset('backend/assets/' . $followArrow) }}"
                                            alt="{{ $followIsPositive ? 'Up Arrow' : 'Down Arrow' }}"
                                            width="24" height="24">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-sm-1 mb-md-1 mb-lg-5 mb-xl-0 col-6 pe-xl-0 ps-xl-0">
        <div class="mandate-section">
            <div class="mandate-item">
                <div class="mandate-item-header" style="box-shadow:  0 15px 13px -7px rgba(0, 0, 0, 0.2); padding: 10px; margin-bottom: 10px;">
                    <div class="text-center">
                        <div class="mandate-item-title">
                            <div class="mandate-item-text">
                                <h2 class="mb-0">
                                    UNFOLLOWERS
                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;"
                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                        data-bs-custom-class="success-tooltip"
                                        data-bs-title="{{ $tooltipText !== '' ? $tooltipText : 'No description available' }}"
                                        aria-describedby="tooltip549868">
                                    </i>
                                </h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mandate-item-body">
                    <div class="mandate-followers-body">
                        <div class="mandate-item-title d-flex justify-content-between align-items-center gap-1">
                            <div class="col-custom-3">
                                <h5 style="margin-bottom: 0px;">Previous Month</h5>
                                <h3 class="follow-font">
                                    {{ compact_number($followersData['unfollows']['previous'] ?? 0) }}
                                </h3>
                            </div>
                            <div class="col-custom-3">
                                <h5 style="margin-bottom: 0px;">Current Month</h5>
                                <h3 class="follow-font">
                                    {{ compact_number($followersData['unfollows']['current'] ?? 0) }}
                                </h3>
                            </div>
                            <div class="col-custom-3">
                                <div class="mandate-item-arrow">
                                    @php
                                    $unfollowPercent = $followersData['unfollows']['percent_change'] ?? 0;
                                    $unfollowIsPositive = $unfollowPercent >= 0;
                                    $unfollowColor = $unfollowIsPositive ? '#28a745' : '#e70000ff';
                                    $unfollowArrow = $unfollowIsPositive ? 'green-arrow-up.png' : 'red-arrow-down.png';
                                    @endphp
                                    <h4 style="margin-bottom: 5px; color: {{ $unfollowColor }}; font-size: 24px;">
                                        {{ abs($unfollowPercent) }}%
                                    </h4>
                                    <div class="mandate-arrow-icon">
                                        <img src="{{ asset('backend/assets/' . $unfollowArrow) }}"
                                            alt="{{ $unfollowIsPositive ? 'Up Arrow' : 'Down Arrow' }}"
                                            width="24" height="24">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>