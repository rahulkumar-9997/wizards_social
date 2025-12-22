@php
$posts = $data['posts'] ?? ['previous' => 0, 'current' => 0, 'change' => 0, 'change_type' => 'down'];
$reels = $data['reels'] ?? ['previous' => 0, 'current' => 0, 'change' => 0, 'change_type' => 'down'];
$postsPrevious = compact_number($posts['previous']);
$postsCurrent = compact_number($posts['current']);
$reelsPrevious = compact_number($reels['previous']);
$reelsCurrent = compact_number($reels['current']);

$postsArrow = $posts['change_type'] === 'up' ? 'green-arrow-up.png' : 'red-arrow-down.png';
$postsColor = $posts['change_type'] === 'up' ? '#00ff00' : '#e70000ff';

$reelsArrow = $reels['change_type'] === 'up' ? 'green-arrow-up.png' : 'red-arrow-down.png';
$reelsColor = $reels['change_type'] === 'up' ? '#00ff00' : '#e70000ff';
@endphp

<div class="col-md-6 mb-sm-1 mb-md-1 mb-lg-5 mb-xl-1 col-6 pe-xl-0 ps-xl-0 mb-2">
    <div class="mandate-section">
        <div class="mandate-item">
            <div class="mandate-item-header" style="box-shadow:  0 15px 13px -7px rgba(0, 0, 0, 0.2); padding: 10px; margin-bottom: 10px;">
                <div class="text-center">
                    <div class="mandate-item-title">
                        <div class="mandate-item-text">
                            <h2 class="mb-0">NO.OF POST</h2>
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
                                {{ $postsPrevious }}
                            </h3>
                        </div>
                        <div class="col-custom-3">
                            <h5 style="margin-bottom: 0px;">Current Month</h5>
                            <h3 class="follow-font">
                                {{ $postsCurrent }}
                            </h3>
                        </div>
                        <div class="col-custom-3">
                            <div class="mandate-item-arrow">
                                <h4 style="margin-bottom: 5px; color: {{ $postsColor }}; font-size: 24px;">
                                    {{ abs($posts['change']) }}%
                                </h4>
                                <div class="mandate-arrow-icon">
                                    <img src="{{ asset('backend/assets/' . $postsArrow) }}" alt="Arrow" width="24" height="24">
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
                            <h2 class="mb-0">NO. OF REELS</h2>
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
                                {{ $reelsPrevious }}
                            </h3>
                        </div>
                        <div class="col-custom-3">
                            <h5 style="margin-bottom: 0px;">Current Month</h5>
                            <h3 class="follow-font">
                                {{ $reelsCurrent }}
                            </h3>
                        </div>
                        <div class="col-custom-3">
                            <div class="mandate-item-arrow">
                                <h4 style="margin-bottom: 5px; color: {{ $reelsColor }}; font-size: 24px;">
                                    {{ abs($reels['change']) }}%
                                </h4>
                                <div class="mandate-arrow-icon">
                                    <img src="{{ asset('backend/assets/' . $reelsArrow) }}" alt="Arrow" width="24" height="24">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>