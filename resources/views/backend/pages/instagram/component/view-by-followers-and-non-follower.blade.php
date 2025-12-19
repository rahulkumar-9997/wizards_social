<div class="pdf-se-title">
    <h3 style="margin-bottom: 0px;">
        VIEW BY FOLLOWERS & NON FOLLOWERS
        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;"
            data-bs-toggle="tooltip" data-bs-placement="top"
            data-bs-custom-class="success-tooltip"
            data-bs-title="{{ $data['follows']['api_description'] ?? 'No description available' }}">
        </i>
    </h3>
</div>
<div class="row">
    <div class="col-md-6 mb-sm-1 mb-md-1 mb-lg-5 mb-xl-1 col-6 pe-xl-0 ps-xl-0 mb-2">
        <div class="mandate-section">
            <div class="mandate-item">
                <div class="mandate-item-header" style="box-shadow:  0 15px 13px -7px rgba(0, 0, 0, 0.2); padding: 10px; margin-bottom: 10px;">
                    <div class="text-center">
                        <div class="mandate-item-title">
                            <div class="mandate-item-text">
                                <h2 class="mb-0">FOLLOWS</h2>
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
                                    {{ compact_number($data['follows']['previous'] ?? 0) }}
                                </h3>
                            </div>
                            <div class="col-custom-3">
                                <h5 style="margin-bottom: 0px;">Current Month</h5>
                                <h3 class="follow-font">
                                    {{ compact_number($data['follows']['current'] ?? 0) }}
                                </h3>
                            </div>
                            <div class="col-custom-3">
                                <div class="mandate-item-arrow">
                                    @php
                                    $followPercentChange = $data['follows']['percent_change'] ?? 0;
                                    $followIsPositive = $followPercentChange >= 0;
                                    $followColor = $followIsPositive ? '#28a745' : '#e70000ff';
                                    $followArrow = $followIsPositive ? 'green-arrow-up.png' : 'red-arrow-down.png';
                                    @endphp
                                    <h4 style="margin-bottom: 5px; color: {{ $followColor }}; font-size: 24px;">
                                        {{ abs($followPercentChange) }}%
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
    <div class="col-md-6 mb-sm-1 mb-md-1 mb-lg-5 mb-xl-1 col-6 pe-xl-0 ps-xl-0 mb-2">
        <div class="mandate-section">
            <div class="mandate-item">
                <div class="mandate-item-header" style="box-shadow:  0 15px 13px -7px rgba(0, 0, 0, 0.2); padding: 10px; margin-bottom: 10px;">
                    <div class="text-center">
                        <div class="mandate-item-title">
                            <div class="mandate-item-text">
                                <h2 class="mb-0">UNFOLLOWS</h2>
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
                                    {{ compact_number($data['unfollows']['previous'] ?? 0) }}
                                </h3>
                            </div>
                            <div class="col-custom-3">
                                <h5 style="margin-bottom: 0px;">Current Month</h5>
                                <h3 class="follow-font">
                                    {{ compact_number($data['unfollows']['current'] ?? 0) }}
                                </h3>
                            </div>
                            <div class="col-custom-3">
                                <div class="mandate-item-arrow">
                                    @php
                                    $unfollowPercentChange = $data['unfollows']['percent_change'] ?? 0;
                                    $unfollowIsPositive = $unfollowPercentChange >= 0;
                                    $unfollowColor = $unfollowIsPositive ? '#28a745' : '#e70000ff';
                                    $unfollowArrow = $unfollowIsPositive ? 'green-arrow-up.png' : 'red-arrow-down.png';
                                    @endphp
                                    <h4 style="margin-bottom: 5px; color: {{ $unfollowColor }}; font-size: 24px;">
                                        {{ abs($unfollowPercentChange) }}%
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