@php
    $likes = $data['likes'] ?? [
        'api_description' => 'The number of likes on your posts, reels and videos.',
        'previous' => 0,
        'current' => 0,
        'change' => 0,
        'change_type' => 'down'
    ];

    $comments = $data['comments'] ?? [
        'api_description' => 'The number of comments on your posts, reels, videos and live videos.',
        'previous' => 0,
        'current' => 0,
        'change' => 0,
        'change_type' => 'down'
    ];

    $saves = $data['saves'] ?? [
        'api_description' => 'The number of saves of your posts, reels and videos.',
        'previous' => 0,
        'current' => 0,
        'change' => 0,
        'change_type' => 'down'
    ];

    $shares = $data['shares'] ?? [
        'api_description' => 'The number of shares of your posts, stories, reels, videos and live videos.',
        'previous' => 0,
        'current' => 0,
        'change' => 0,
        'change_type' => 'down'
    ];

    $reposts = $data['reposts'] ?? [
        'api_description' => 'The total number of times that your content was reposted.',
        'previous' => 0,
        'current' => 0,
        'change' => 0,
        'change_type' => 'down'
    ];
    function formatPercentage($change, $changeType) {
        $arrow = $changeType === 'up' ? '▲' : '▼';
        $color = $changeType === 'up' ? 'text-success' : 'text-danger';
        return '<small class="' . $color . '">' . $arrow . ' ' . abs($change) . '%</small>';
    }
@endphp

<div class="total-interactions-like-comm">
    <div class="col-lg-12 mb-2">
        <h5 class="card-title mb-0">Total Interactions by Likes, Comments, Saves, Shares, Reposts</h5>
    </div>
    <table class="table table-bordered table-sm interactions-table">
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
                                        Likes
                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-custom-class="success-tooltip"
                                            data-bs-title="{{ htmlspecialchars($likes['api_description'], ENT_QUOTES) }}">
                                        </i>
                                    </h4>
                                </td>
                                <td>
                                    <h4 class="mb-0">{{ compact_number($likes['previous']) }}</h4>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h4 class="mb-0">
                                        Comments
                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-custom-class="success-tooltip"
                                            data-bs-title="{{ htmlspecialchars($comments['api_description'], ENT_QUOTES) }}">
                                        </i>
                                    </h4>
                                </td>
                                <td>
                                    <h4 class="mb-0">{{ compact_number($comments['previous']) }}</h4>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h4 class="mb-0">
                                        Saves
                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-custom-class="success-tooltip"
                                            data-bs-title="{{ htmlspecialchars($saves['api_description'], ENT_QUOTES) }}">
                                        </i>
                                    </h4>
                                </td>
                                <td>
                                    <h4 class="mb-0">{{ compact_number($saves['previous']) }}</h4>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h4 class="mb-0">
                                        Shares
                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-custom-class="success-tooltip"
                                            data-bs-title="{{ htmlspecialchars($shares['api_description'], ENT_QUOTES) }}">
                                        </i>
                                    </h4>
                                </td>
                                <td>
                                    <h4 class="mb-0">{{ compact_number($shares['previous']) }}</h4>
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
                                        Likes
                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-custom-class="success-tooltip"
                                            data-bs-title="{{ htmlspecialchars($likes['api_description'], ENT_QUOTES) }}">
                                        </i>
                                    </h4>
                                </td>
                                <td>
                                    <h4 class="mb-0">
                                        {{ compact_number($likes['current']) }}
                                        {!! formatPercentage($likes['change'], $likes['change_type']) !!}
                                    </h4>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h4 class="mb-0">
                                        Comments
                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-custom-class="success-tooltip"
                                            data-bs-title="{{ htmlspecialchars($comments['api_description'], ENT_QUOTES) }}">
                                        </i>
                                    </h4>
                                </td>
                                <td>
                                    <h4 class="mb-0">
                                        {{ compact_number($comments['current']) }}
                                        {!! formatPercentage($comments['change'], $comments['change_type']) !!}
                                    </h4>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h4 class="mb-0">
                                        Saves
                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-custom-class="success-tooltip"
                                            data-bs-title="{{ htmlspecialchars($saves['api_description'], ENT_QUOTES) }}">
                                        </i>
                                    </h4>
                                </td>
                                <td>
                                    <h4 class="mb-0">
                                        {{ compact_number($saves['current']) }}
                                        {!! formatPercentage($saves['change'], $saves['change_type']) !!}
                                    </h4>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h4 class="mb-0">
                                        Shares
                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            data-bs-custom-class="success-tooltip"
                                            data-bs-title="{{ htmlspecialchars($shares['api_description'], ENT_QUOTES) }}">
                                        </i>
                                    </h4>
                                </td>
                                <td>
                                    <h4 class="mb-0">
                                        {{ compact_number($shares['current']) }}
                                        {!! formatPercentage($shares['change'], $shares['change_type']) !!}
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