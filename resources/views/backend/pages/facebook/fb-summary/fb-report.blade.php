@extends('backend.pages.layouts.master')
@section('title', ($facebookBusinessOrProfile['name'] ?? 'Facebook') .
' Dashboard' .
(!empty($facebookBusinessOrProfile['followers_count'])
? ' – ' . number_format($facebookBusinessOrProfile['followers_count']) . ' Followers'
: '')
)
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous" />
<style>
    .metric-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        padding: 10px;
        transition: all 0.3s ease;
        height: 100%;
    }

    .metric-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
    }

    .metric-header {
        font-size: 22px;
        font-weight: 600;
        color: #313b5e;
        border-bottom: 2px solid #f1f1f1;
    }

    .metric-body h3 {
        font-weight: 700;
        font-size: 1.5rem;
    }

    .metric-body h4 {
        font-weight: 600;
        font-size: 1.2rem;
    }

    .metric-body table {
        width: 100%;
        border-collapse: collapse;
    }

    .metric-body table td,
    .metric-body table th {
        padding: 8px;
    }
     .positive {
        background-color: #28a745 !important;
        color: #fff !important;
    }

    .positive h4,
    .negative h4 {
        color: #fff;
    }

    .negative {
        background-color: #dc3545 !important;
        color: #fff !important;
    }

    .neutral {
        background-color: #f1f3f5 !important;
        color: #333 !important;
        border-radius: 8px;
    }

    .metrics-table {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        background: #fff;
    }
</style>
@endpush
@section('main-content')
<div class="container-fluid">
    <div class="row">
        @include('backend.pages.layouts.second-sidebar', [
        'selectedInstagramId' => $instagram['id'] ?? null,
        'selectedFbId' => $facebookBusinessOrProfile['id'] ?? null
        ])
        <div class="col-xl-9 export_pdf_report">
            <div class="row mb-2">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-1">
                            <h4 class="card-title mb-0">
                                Facebook
                                <span class="text-success">{{ $facebookBusinessOrProfile['name'] ?? '' }}</span>
                            </h4>
                            <button id="downloadPdf" class="btn btn-outline-primary pdf-download-btn no-print">
                                <i class="bx bx-download"></i> Download PDF Report
                            </button>
                        </div>

                        <div class="card-body d-flex align-items-center flex-wrap">
                            <img src="{{ $facebookBusinessOrProfile['picture']['data']['url'] ?? asset('images/default-profile.png') }}"
                                width="100" height="100" class="me-3 rounded-circle border" alt="Profile">

                            <div>
                                <h3 class="mb-1 fw-bold">{{ $facebookBusinessOrProfile['name'] ?? 'Facebook Page' }} </h3>
                                <h4 class="mb-1 fw-bold">({{ implode(', ', $facebookBusinessOrProfile['emails'] ?? []) }})</h4>
                                <h4 class="mb-1 fw-bold">{{ $facebookBusinessOrProfile['category'] ?? '' }}</h4>
                                <p class="text-muted mb-1">{{ $facebookBusinessOrProfile['username'] ?? '' }}</p>
                                @if(!empty($facebookBusinessOrProfile['about']))
                                <p class="mb-2">{!! nl2br(e($facebookBusinessOrProfile['about'])) !!}</p>
                                @endif

                                <div class="d-flex gap-4 flex-wrap">
                                    <!-- <span><strong>{{ number_format($facebookBusinessOrProfile['media_count'] ?? 0) }}</strong> posts</span> -->
                                    <span><strong>{{ number_format($facebookBusinessOrProfile['fan_count'] ?? 0) }}</strong> Fan </span>
                                    <span><strong>{{ number_format($facebookBusinessOrProfile['followers_count'] ?? 0) }}</strong> Followers</span>
                                    <span><strong>{{ number_format($facebookBusinessOrProfile['rating_count'] ?? 0) }}</strong> Rating</span>
                                </div>
                            </div>

                            <div class="ms-auto">
                                <a href="{{ route('facebook.index') }}" class="btn btn-outline-primary">
                                    <i class="fab fa-facebook"></i> Back to Facebook
                                </a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-xxl-12">
                    <div id="insta_face_dashboard">
                        <div class="card">
                            <div class="card-header text-white">
                                <h4 class="card-title mb-0">
                                    Performance 
                                    (<span class="text-info">14 October 2025 - 10 November 2025</span>)
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="reach-section mb-4">
                                    <div class="row g-4">
                                        <div class="col-md-4 reach col-sm-4 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-0 pe-xl-0 ps-xl-2">
                                            <div class="metric-card">
                                                <div class="metric-header card-header">
                                                    <h4 class="mb-0">
                                                        Reach
                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of unique accounts that have seen your content, at least once, including in ads. Content includes posts, stories, reels, videos and live videos. Reach is different from impressions, which may include multiple views of your content by the same accounts. This metric is estimated and in development.">
                                                        </i>
                                                    </h4>
                                                </div>
                                                <div class="card-body">
                                                    <table class="table table-sm mb-2 align-middle text-center">
                                                        <tbody>
                                                            <tr>
                                                                <th>
                                                                    <h3 class="mb-0">335.6K</h3>
                                                                </th>
                                                                <th>
                                                                    <h3 class="mb-0">690.3K</h3>
                                                                </th>
                                                            </tr>
                                                            <tr>
                                                                <td class="bg-black text-light">Previous Month</td>
                                                                <td class="bg-black text-light">Current Month</td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="2" class="positive">
                                                                    <h4 class="mb-0">▲ +105.68%</h4>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="bg-black text-light">Paid Reach</td>
                                                                <td class="bg-black text-light">Organic Reach</td>
                                                            </tr>
                                                            <tr>
                                                                <td>
                                                                    <h4 class="mb-0">421.8K</h4>
                                                                </td>
                                                                <td>
                                                                    <h4 class="mb-0">268.6K</h4>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="bg-black text-light">Followers</td>
                                                                <td class="bg-black text-light">Non-Followers</td>
                                                            </tr>
                                                            <tr>
                                                                <td>
                                                                    <h4 class="mb-0">14.7K</h4>
                                                                </td>
                                                                <td>
                                                                    <h4 class="mb-0">675.6K</h4>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 followers">
                                            <div class="row">
                                                <div class="col-md-12 col-sm-12 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2">
                                                    <div class="metric-card">
                                                        <div class="metric-header">
                                                            <h4>
                                                                Followers
                                                                <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of accounts that followed you and the number of accounts that unfollowed you or left Instagram in the selected time period.">
                                                                </i>
                                                            </h4>
                                                        </div>
                                                        <div class="metric-body">
                                                            <table class="table table-sm mb-2 align-middle text-center">

                                                                <tbody>
                                                                    <tr>
                                                                        <th>
                                                                            <h3 class="mb-0">
                                                                                568
                                                                            </h3>
                                                                        </th>
                                                                        <th>
                                                                            <h3 class="mb-0">
                                                                                1196
                                                                            </h3>
                                                                        </th>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="bg-black text-light">Previous Month</td>
                                                                        <td class="bg-black text-light">Current Month</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td colspan="2" class="positive">
                                                                            <h4 class="mb-0">▲ +110.56%</h4>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-12 col-sm-12 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2">
                                                    <div class="metric-card">
                                                        <div class="metric-header">
                                                            <h4>
                                                                Unfollowers
                                                                <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of accounts that followed you and the number of accounts that unfollowed you or left Instagram in the selected time period.">
                                                                </i>
                                                            </h4>
                                                        </div>
                                                        <div class="metric-body">
                                                            <table class="table table-sm mb-2 align-middle text-center">

                                                                <tbody>
                                                                    <tr>
                                                                        <th>
                                                                            <h3 class="mb-0">
                                                                                593
                                                                            </h3>
                                                                        </th>
                                                                        <th>
                                                                            <h3 class="mb-0">
                                                                                273
                                                                            </h3>
                                                                        </th>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="bg-black text-light">Previous Month</td>
                                                                        <td class="bg-black text-light">Current Month</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td colspan="2" class="negative">
                                                                            <h4 class="mb-0">▼ 53.96%</h4>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 view col-sm-4 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2 mb-1">
                                            <div class="metric-card">
                                                <div class="metric-header">
                                                    <h4>
                                                        View
                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of times your content was played or displayed. Content includes reels, posts, stories.">
                                                        </i>
                                                    </h4>
                                                </div>
                                                <div class="metric-body">
                                                    <table class="table table-sm mb-2 align-middle text-center">
                                                        <tbody>
                                                            <tr>
                                                                <td colspan="2" class="bg-black text-light">Followers</td>
                                                            </tr>
                                                            <tr>
                                                                <td>
                                                                    <h4 class="mb-0">185K</h4>
                                                                </td>
                                                                <td>
                                                                    <h4 class="mb-0">158.6K</h4>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="bg-black text-light">Previous Month</td>
                                                                <td class="bg-black text-light">Current Month</td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="2" class="negative">
                                                                    <h4 class="mb-0">▼ 14.25%</h4>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="2" class="bg-black text-light">Non Followers</td>
                                                            </tr>
                                                            <tr>
                                                                <td>
                                                                    <h4 class="mb-0">660.6K</h4>
                                                                </td>
                                                                <td>
                                                                    <h4 class="mb-0">1.2M</h4>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="bg-black text-light">Previous Month</td>
                                                                <td class="bg-black text-light">Current Month</td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="2" class="positive">
                                                                    <h4 class="mb-0">▲ +76.02%</h4>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-4">
                                    <div class="col-md-12 col-sm-6 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2 mb-1">
                                        <div class="row">
                                            <div class="col-md-12 col-sm-12 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2 mb-1">
                                                <div class="metric-card">
                                                    <div class="metric-header">
                                                        <h4>
                                                            Total Interactions
                                                            <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                            </i>
                                                        </h4>
                                                    </div>
                                                    <div class="metric-body">
                                                        <table class="table table-sm mb-3 align-middle text-center">

                                                            <tbody>
                                                                <tr>
                                                                    <td>
                                                                        <h4 class="mb-0">4.1K</h4>
                                                                    </td>
                                                                    <td>
                                                                        <h4 class="mb-0">10.8K</h4>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="bg-black text-light">Previous Month</td>
                                                                    <td class="bg-black text-light">Current Month</td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2" class="positive">
                                                                        <h4 class="mb-0">▲ +163.4%</h4>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                        <div class="row">
                                                            <div class="col-lg-6">
                                                                <div class="col-lg-12 mb-2">
                                                                    <h5 class="card-title mb-0">Total Interactions by Likes, Comments, Saves, Shares, Reposts </h5>
                                                                </div>
                                                                <table class="table table-bordered table-sm mb-2 ">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td class="bg-black text-light">Previous Month</td>
                                                                            <td class="bg-black text-light">Current Month</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <!-- Previous Month -->
                                                                            <td>
                                                                                <table class="table table-sm mb-2 ">
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <h4 class="mb-0">
                                                                                                    Likes
                                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of likes on your posts, reels and videos.">
                                                                                                    </i>
                                                                                                </h4>
                                                                                            </td>
                                                                                            <td>
                                                                                                <h4 class="mb-0">2.4K</h4>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <h4 class="mb-0">
                                                                                                    Comments
                                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of comments on your posts, reels, videos and live videos.">
                                                                                                    </i>
                                                                                                </h4>
                                                                                            </td>
                                                                                            <td>
                                                                                                <h4 class="mb-0">45</h4>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <h4 class="mb-0">
                                                                                                    Saves
                                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of saves of your posts, reels and videos.">
                                                                                                    </i>
                                                                                                </h4>
                                                                                            </td>
                                                                                            <td>
                                                                                                <h4 class="mb-0">531</h4>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <h4 class="mb-0">
                                                                                                    Shares
                                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of shares of your posts, stories, reels, videos and live videos.">
                                                                                                    </i>
                                                                                                </h4>
                                                                                            </td>
                                                                                            <td>
                                                                                                <h4 class="mb-0">627</h4>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <h4 class="mb-0">
                                                                                                    Reposts
                                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of times your content was reposted. A repost occurs when another account shares your content to their own profile.">
                                                                                                    </i>
                                                                                                </h4>
                                                                                            </td>
                                                                                            <td>
                                                                                                <h4 class="mb-0">14</h4>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </td>

                                                                            <!-- Current Month -->
                                                                            <td>
                                                                                <table class="table  table-sm mb-2">
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <h4 class="mb-0">
                                                                                                    Likes
                                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of likes on your posts, reels and videos.">
                                                                                                    </i>
                                                                                                </h4>
                                                                                            </td>
                                                                                            <td>
                                                                                                <h4 class="mb-0">
                                                                                                    7.5K
                                                                                                    <small class="text-success">
                                                                                                        ▲ +211.4%</small>
                                                                                                </h4>

                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <h4 class="mb-0">
                                                                                                    Comments
                                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of comments on your posts, reels, videos and live videos.">
                                                                                                    </i>
                                                                                                </h4>
                                                                                            </td>
                                                                                            <td>
                                                                                                <h4 class="mb-0">
                                                                                                    81
                                                                                                    <small class="text-success">
                                                                                                        ▲ +80%</small>
                                                                                                </h4>

                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <h4 class="mb-0">
                                                                                                    Saves
                                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of saves of your posts, reels and videos.">
                                                                                                    </i>
                                                                                                </h4>
                                                                                            </td>
                                                                                            <td>
                                                                                                <h4 class="mb-0">
                                                                                                    817
                                                                                                    <small class="text-success">
                                                                                                        ▲ +53.86%</small>
                                                                                                </h4>

                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <h4 class="mb-0">
                                                                                                    Shares
                                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of shares of your posts, stories, reels, videos and live videos.">
                                                                                                    </i>
                                                                                                </h4>
                                                                                            </td>
                                                                                            <td>
                                                                                                <h4 class="mb-0">
                                                                                                    1.2K
                                                                                                    <small class="text-success">
                                                                                                        ▲ +89.63%</small>
                                                                                                </h4>

                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <h4 class="mb-0">
                                                                                                    Reposts
                                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of times your content was reposted. A repost occurs when another account shares your content to their own profile.">
                                                                                                    </i>
                                                                                                </h4>
                                                                                            </td>
                                                                                            <td>
                                                                                                <h4 class="mb-0">
                                                                                                    30
                                                                                                    <small class="text-success">
                                                                                                        ▲ +114.29%</small>
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
                                                            <div class="col-lg-6">
                                                                <div class="col-lg-12 mb-2">
                                                                    <h5 class="card-title mb-0">Total Interactions by Media Type</h5>
                                                                </div>
                                                                <table class="table table-bordered table-sm mb-2">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td class="bg-black text-light">Previous Month</td>
                                                                            <td class="bg-black text-light">Current Month</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <!-- Previous Month -->
                                                                            <td>
                                                                                <table class="table table-sm mb-2">
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <h4 class="mb-0">Post
                                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                                    </i>
                                                                                                </h4>
                                                                                            </td>
                                                                                            <td>
                                                                                                <h4 class="mb-0">234</h4>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <h4 class="mb-0">Ad
                                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                                    </i>
                                                                                                </h4>
                                                                                            </td>
                                                                                            <td>
                                                                                                <h4 class="mb-0">655</h4>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <h4 class="mb-0">Reel
                                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                                    </i>
                                                                                                </h4>
                                                                                            </td>
                                                                                            <td>
                                                                                                <h4 class="mb-0">3.4K</h4>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <h4 class="mb-0">Story
                                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                                    </i>
                                                                                                </h4>
                                                                                            </td>
                                                                                            <td>
                                                                                                <h4 class="mb-0">144</h4>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </td>

                                                                            <!-- Current Month -->
                                                                            <td>
                                                                                <table class="table table-sm mb-2">
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <h4 class="mb-0">Post
                                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                                    </i>
                                                                                                </h4>
                                                                                            </td>
                                                                                            <td>
                                                                                                <h4 class="mb-0">
                                                                                                    254
                                                                                                    <small class="text-success">▲ +8.55%</small>
                                                                                                </h4>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <h4 class="mb-0">Ad
                                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                                    </i>
                                                                                                </h4>
                                                                                            </td>
                                                                                            <td>
                                                                                                <h4 class="mb-0">
                                                                                                    5.4K
                                                                                                    <small class="text-success">▲ +721.53%</small>
                                                                                                </h4>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <h4 class="mb-0">Reel
                                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                                    </i>
                                                                                                </h4>
                                                                                            </td>
                                                                                            <td>
                                                                                                <h4 class="mb-0">
                                                                                                    5.1K
                                                                                                    <small class="text-success">▲ +53.1%</small>
                                                                                                </h4>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <h4 class="mb-0">Story
                                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                                    </i>
                                                                                                </h4>
                                                                                            </td>
                                                                                            <td>
                                                                                                <h4 class="mb-0">
                                                                                                    46
                                                                                                    <small class="text-danger">▼ 68.06%</small>
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
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <!-- Profile Visits -->
                                    <div class="col-md-4 col-sm-4 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-0 pe-xl-0 ps-xl-2">
                                        <div class="metric-card">
                                            <div class="metric-header">
                                                <h4>
                                                    Profile Visits
                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of times that your profile was visited.">
                                                    </i>
                                                </h4>
                                            </div>
                                            <div class="metric-body">
                                                <table class="table table-sm mb-2 align-middle text-center">
                                                    <tbody>
                                                        <tr>
                                                            <th>
                                                                <h3 class="mb-0">8.9K</h3>
                                                            </th>
                                                            <th>
                                                                <h3 class="mb-0">17.1K</h3>
                                                            </th>
                                                        </tr>
                                                        <tr>
                                                            <td class="bg-black text-light">Previous Month</td>
                                                            <td class="bg-black text-light">Current Month</td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="2" class="positive">
                                                                <h4 class="mb-0">▲ +92.16%</h4>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Profile Link Clicks -->
                                    <div class="col-md-4 col-sm-4 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-0 pe-xl-0 ps-xl-2">
                                        <div class="row">
                                            <div class="col-md-12 col-sm-12 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2">
                                                <div class="metric-card">
                                                    <div class="metric-header">
                                                        <h4>
                                                            Profile Link Clicks
                                                            <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of taps on your business address, call button, email button and text button.">
                                                            </i>
                                                        </h4>
                                                    </div>
                                                    <div class="metric-body">
                                                        <table class="table table-sm mb-2 align-middle text-center">

                                                            <tbody>
                                                                <tr>
                                                                    <th>
                                                                        <h3 class="mb-0">10</h3>
                                                                    </th>
                                                                    <th>
                                                                        <h3 class="mb-0">8</h3>
                                                                    </th>
                                                                </tr>
                                                                <tr>
                                                                    <td class="bg-black text-light">Previous Month</td>
                                                                    <td class="bg-black text-light">Current Month</td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2" class="negative">
                                                                        <h4 class="mb-0">▼ 20%</h4>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- Engagement -->
                                    <div class="col-md-4 col-sm-4 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2">
                                        <div class="row">
                                            <div class="col-md-12 col-sm-12 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2 mb-1">
                                                <div class="metric-card">
                                                    <div class="metric-header">
                                                        <h4>
                                                            Engagement
                                                            <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of accounts that have interacted with your content, including in ads. Content includes posts, stories, reels, videos and live videos. Interactions can include actions such as likes, saves, comments, shares or replies. These metrics are estimated and in development.">
                                                            </i>
                                                        </h4>
                                                    </div>
                                                    <div class="metric-body">
                                                        <table class="table table-sm mb-2 align-middle text-center">
                                                            <tbody>
                                                                <tr>
                                                                    <th>
                                                                        <h3 class="mb-0">2.9K</h3>
                                                                    </th>
                                                                    <th>
                                                                        <h3 class="mb-0">8.7K</h3>
                                                                    </th>
                                                                </tr>
                                                                <tr>
                                                                    <td class="bg-black text-light">Previous Month</td>
                                                                    <td class="bg-black text-light">Current Month</td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2" class="positive">
                                                                        <h4 class="mb-0">▲ +202.33%</h4>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <div class="row g-4">
                                    <div class="post-section">
                                        <div class="row justify-content-center">
                                            <div class="col-lg-12">
                                                <div class="table-responsive metrics-table mt-3">
                                                    <table class="table table-bordered align-middle text-center mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th colspan="2">Number of Posts</th>
                                                                <th colspan="2">Number of Stories</th>
                                                                <th colspan="2">Number of Reels</th>
                                                            </tr>
                                                            <tr>
                                                                <th class="metric-section-header">Prev. Month</th>
                                                                <th class="metric-section-header">Current</th>
                                                                <th class="metric-section-header">Prev. Month</th>
                                                                <th class="metric-section-header">Current</th>
                                                                <th class="metric-section-header">Prev. Month</th>
                                                                <th class="metric-section-header">Current</th>

                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td class="highlight">5</td>
                                                                <td>4</td>
                                                                <td class="highlight">0</td>
                                                                <td>0</td>
                                                                <td class="highlight">19</td>
                                                                <td>19</td>

                                                            </tr>
                                                            <tr>
                                                                <td colspan="2" style="background-color: #dc3545; color: #fff; font-weight:600;">-20%</td>
                                                                <td colspan="2" style="background-color: #f8f9fa;">0%</td>
                                                                <td colspan="2" style="background-color: #f8f9fa;">0%</td>

                                                            </tr>

                                                        </tbody>
                                                    </table>
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
            <div class="row mb-2">
                <div class="col-xxl-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-1">
                            <h4 class="card-title mb-0">
                                Top 10 Cities Audience
                                <i id="audienceByCitiesTitle"
                                    class="bx bx-question-mark text-primary"
                                    style="cursor: pointer; font-size: 18px;"
                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                    data-bs-custom-class="warning-tooltip">
                                </i>
                            </h4>
                            <select id="timeframe" class="form-select form-select-sm w-auto">
                                <option value="this_month" selected>This Month</option>
                                <option value="this_week">This Week</option>
                            </select>
                        </div>
                        <div class="card-body">
                            <div id="geolocationContainer">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-1">
                            <h4 class="card-title mb-0">
                                Audience By Age Group
                                <i id="audienceByAgeGroup"
                                    class="bx bx-question-mark text-primary"
                                    style="cursor: pointer; font-size: 18px;"
                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                    data-bs-custom-class="info-tooltip">
                                </i>
                            </h4>
                            <select id="ageTimeframe" class="form-select form-select-sm" style="width: 150px;">
                                <option value="this_week">This Week</option>
                                <option value="this_month" selected>This Month</option>
                            </select>
                        </div>
                        <div class="card-body">
                            <div id="audienceAgeGroupContainer">
                                <canvas id="audienceAgeGroupChart" height="450"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--Instagram reach days wise -->
            <div class="row mb-2">
                <div class="col-xxl-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-1">
                            <h5 class="card-title mb-0">Profile Reach Per Day
                                <i id="profileReachTitle"
                                    class="bx bx-question-mark text-primary"
                                    style="cursor: pointer; font-size: 18px;"
                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                    data-bs-custom-class="danger-tooltip">
                                </i>
                            </h5>
                            <small id="reachDateRange" class="text-muted"></small>
                        </div>
                        <div class="card-body">
                            <div id="reachDaysContainer">
                                <canvas id="reachDaysChart" height="450"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-1">
                            <h5 class="card-title mb-0">
                                Instagram Views
                                <i id="viewDateRangeTitle"
                                    class="bx bx-question-mark text-primary"
                                    style="cursor: pointer; font-size: 18px;"
                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                    data-bs-custom-class="success-tooltip">
                                </i>
                            </h5>
                            <span id="viewDateRange" class="text-muted small"></span>
                        </div>
                        <div class="card-body">
                            <div id="viewDaysContainer">

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-xxl-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-1">
                            <h4 class="card-title mb-0">Instagram Post</h4>
                        </div>
                        <div class="card-body">
                            <div class="instagram_post">
                                <div class="col-lg-12">
                                    <div id="post-filter" class="filter-box">
                                        <div class="d-flex flex-wrap align-items-center bg-white p-2 gap-1">
                                            <div class="d-flex align-items-center border-end pe-1">
                                                <select id="media-type-filter" class="form-select form-select-md">
                                                    <option value="">All Types</option>
                                                    <option value="CAROUSEL_ALBUM">Photos</option>
                                                    <option value="VIDEO">Video</option>
                                                    <option value="REELS">Reels</option>
                                                </select>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <input type="search" id="post-search" class="form-control form-control-md" placeholder="Search by ID or Caption">
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button id="reset-filters" class="btn btn-danger">
                                                    <i class="bx bx-reset me-1"></i> Reset
                                                </button>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <div id="instagram_post">

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection
    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <script>
        window.facebook_base_url = "{{ url('facebook-summary') }}";
    </script>
    @endpush