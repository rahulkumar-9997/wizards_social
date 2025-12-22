@php
$interactions = $data['total_interactions'] ?? [
'previous' => 0,
'current' => 0,
'change' => 0,
'change_type' => 'down',
'description' => 'Total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.'
];
$interactionsPrevious = compact_number($interactions['previous']);
$interactionsCurrent = compact_number($interactions['current']);
$arrow = $interactions['change_type'] === 'up' ? 'green-arrow-up.png' : 'red-arrow-down.png';
$color = $interactions['change_type'] === 'up' ? '#00ff00' : '#e70000';

$tooltipDescription = $interactions['description'] ?? 'Total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.';
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
                                    TOTAL INTERACTIONS
                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="{{ htmlspecialchars($tooltipDescription, ENT_QUOTES) }}">
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
                                    {{ $interactionsPrevious }}
                                </h3>
                            </div>
                            <div class="col-custom-3">
                                <h5 style="margin-bottom: 0px;">Current Month</h5>
                                <h3 class="follow-font">
                                    {{ $interactionsCurrent }}
                                </h3>
                            </div>
                            <div class="col-custom-3">
                                <div class="mandate-item-arrow">
                                    <h4 style="margin-bottom: 5px; color: {{ $color }}; font-size: 24px;">
                                        {{ $interactions['change'] }}%
                                    </h4>
                                    <div class="mandate-arrow-icon">
                                        <img src="{{ asset('backend/assets/' . $arrow) }}" alt="{{ $interactions['change_type'] === 'up' ? 'Up Arrow' : 'Down Arrow' }}" width="24" height="24">
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