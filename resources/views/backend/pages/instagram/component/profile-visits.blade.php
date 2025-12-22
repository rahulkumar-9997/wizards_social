@php
    $profileVisits = $data['profile_visits'] ?? [
        'previous' => 0,
        'current' => 0,
        'change' => 0,
        'change_type' => 'down',
        'description' => 'The number of times that your profile was visited.'
    ];
   
    $profilePrevious = compact_number($profileVisits['previous']);
    $profileCurrent = compact_number($profileVisits['current']);
    $arrow = $profileVisits['change_type'] === 'up' ? 'green-arrow-up.png' : 'red-arrow-down.png';
    $color = $profileVisits['change_type'] === 'up' ? '#00ff00' : '#e70000';
    $tooltipDescription = $profileVisits['description'] ?? 'The number of times that your profile was visited.';
@endphp
<div class="row align-items-center">
    <div class="col-md-7 mb-sm-1 mb-md-1 mb-lg-5 mb-xl-1 col-6 pe-xl-0 ps-xl-0 mb-2">
        <div class="mandate-section">
            <div class="mandate-item">
                <div class="mandate-item-header" style="box-shadow:  0 15px 13px -7px rgba(0, 0, 0, 0.2); padding: 10px; margin-bottom: 10px;">
                    <div class="text-center">
                        <div class="mandate-item-title">
                            <div class="mandate-item-text">
                                <h2 class="mb-0">
                                    PROFILE VISITS
                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="{{ htmlspecialchars($tooltipDescription, ENT_QUOTES) }}" aria-describedby="tooltip511529">
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
                                   {{ $profilePrevious }}
                                </h3>
                            </div>
                            <div class="col-custom-3">
                                <h5 style="margin-bottom: 0px;">Current Month</h5>
                                <h3 class="follow-font">
                                     {{ $profileCurrent }}
                                </h3>
                            </div>
                            <div class="col-custom-3">
                                <div class="mandate-item-arrow">
                                    <h4 style="margin-bottom: 5px; color: #e70000ff; font-size: 24px;">
                                        {{ $profileVisits['change'] }}%
                                    </h4>
                                    <div class="mandate-arrow-icon">
                                         <img src="{{ asset('backend/assets/' . $arrow) }}" 
                                             alt="{{ $profileVisits['change_type'] === 'up' ? 'Up Arrow' : 'Down Arrow' }}" 
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
    <div class="col-md-5 mb-sm-1 mb-md-1 mb-lg-5 mb-xl-1 col-6 pe-xl-0 ps-xl-0 mb-2">
        <div class="single-content">
            <p class="text-justify">
                It shows how many different
                people discovered your
                profile, not how many times it
                was viewed. Profile reach
                helps you understand your
                brand visibility and audience
                interest. Higher profile reach
                often means your content is
                attracting new users to your
                page.
            </p>
        </div>
    </div>
</div>