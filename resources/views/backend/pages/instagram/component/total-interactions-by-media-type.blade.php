@php
$post = $data['post'] ?? [
    'api_description' => 'The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.',
    'previous' => 0,
    'current' => 0,
    'percent' => 0,
    'change' => 0,
    'change_type' => 'down',
    'status' => '-'
];

$ad = $data['ad'] ?? [
    'api_description' => 'The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.',
    'previous' => 0,
    'current' => 0,
    'percent' => 0,
    'change' => 0,
    'change_type' => 'down',
    'status' => '-'
];

$reel = $data['reel'] ?? [
    'api_description' => 'The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.',
    'previous' => 0,
    'current' => 0,
    'percent' => 0,
    'change' => 0,
    'change_type' => 'down',
    'status' => '-'
];

$story = $data['story'] ?? [
    'api_description' => 'The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.',
    'previous' => 0,
    'current' => 0,
    'percent' => 0,
    'change' => 0,
    'change_type' => 'down',
    'status' => '-'
];
function formatMediaPercentage($change, $changeType) {
    if ($change == 0) {
    return '';
    }
    $arrow = $changeType === 'up' ? '▲' : '▼';
    $color = $changeType === 'up' ? 'text-success' : 'text-danger';
    return '<small class="' . $color . '">' . $arrow . ' ' . abs($change) . '%</small>';
}
@endphp

<div class="interactions-by-media-type-section">
    <div class="col-lg-12 mb-2">
        <h5 class="card-title mb-0">Total Interactions by Media Type</h5>
    </div>
    <table class="table table-bordered table-sm mb-2 interactions-table">
        <tbody>
            <tr>
                <td class="bg-black text-light">Previous Month</td>
                <td class="bg-black text-light">Current Month</td>
            </tr>
            <tr>
                <td>
                    <table class="table table-sm interface-table">
                        <tbody>
                            <tr>
                                <td>
                                    <h4 class="mb-0">
                                        Post
                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-custom-class="success-tooltip"
                                            data-bs-title="{{ htmlspecialchars($post['api_description'], ENT_QUOTES) }}">
                                        </i>
                                    </h4>
                                </td>
                                <td>
                                    <h4 class="mb-0">{{ compact_number($post['previous']) }}</h4>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h4 class="mb-0">
                                        Ad
                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-custom-class="success-tooltip"
                                            data-bs-title="{{ htmlspecialchars($ad['api_description'], ENT_QUOTES) }}">
                                        </i>
                                    </h4>
                                </td>
                                <td>
                                    <h4 class="mb-0">{{ compact_number($ad['previous']) }}</h4>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h4 class="mb-0">
                                        Reel
                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-custom-class="success-tooltip"
                                            data-bs-title="{{ htmlspecialchars($reel['api_description'], ENT_QUOTES) }}">
                                        </i>
                                    </h4>
                                </td>
                                <td>
                                    <h4 class="mb-0">{{ compact_number($reel['previous']) }}</h4>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h4 class="mb-0">
                                        Story
                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-custom-class="success-tooltip"
                                            data-bs-title="{{ htmlspecialchars($story['api_description'], ENT_QUOTES) }}">
                                        </i>
                                    </h4>
                                </td>
                                <td>
                                    <h4 class="mb-0">{{ compact_number($story['previous']) }}</h4>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>

                <!-- Current Month -->
                <td>
                    <table class="table table-sm interface-table">
                        <tbody>
                            <tr>
                                <td>
                                    <h4 class="mb-0">
                                        Post
                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-custom-class="success-tooltip"
                                            data-bs-title="{{ htmlspecialchars($post['api_description'], ENT_QUOTES) }}">
                                        </i>
                                    </h4>
                                </td>
                                <td>
                                    <h4 class="mb-0">
                                        {{ compact_number($post['current']) }}
                                        {!! formatMediaPercentage($post['change'], $post['change_type']) !!}
                                    </h4>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h4 class="mb-0">
                                        Ad
                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-custom-class="success-tooltip"
                                            data-bs-title="{{ htmlspecialchars($ad['api_description'], ENT_QUOTES) }}">
                                        </i>
                                    </h4>
                                </td>
                                <td>
                                    <h4 class="mb-0">
                                        {{ compact_number($ad['current']) }}
                                        {!! formatMediaPercentage($ad['change'], $ad['change_type']) !!}
                                    </h4>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h4 class="mb-0">
                                        Reel
                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-custom-class="success-tooltip"
                                            data-bs-title="{{ htmlspecialchars($reel['api_description'], ENT_QUOTES) }}">
                                        </i>
                                    </h4>
                                </td>
                                <td>
                                    <h4 class="mb-0">
                                        {{ compact_number($reel['current']) }}
                                        {!! formatMediaPercentage($reel['change'], $reel['change_type']) !!}
                                    </h4>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h4 class="mb-0">
                                        Story
                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-custom-class="success-tooltip"
                                            data-bs-title="{{ htmlspecialchars($story['api_description'], ENT_QUOTES) }}">
                                        </i>
                                    </h4>
                                </td>
                                <td>
                                    <h4 class="mb-0">
                                        {{ compact_number($story['current']) }}
                                        {!! formatMediaPercentage($story['change'], $story['change_type']) !!}
                                    </h4>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
</div>