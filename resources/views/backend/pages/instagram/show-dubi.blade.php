@extends('backend.pages.layouts.master')
@section('title', 'Facebook Integration')
@push('styles')

@endpush

@section('main-content')
<div class="container-fluid">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <strong>{{ session('success') }}</strong>
        @if(session('info'))
        <div class="mt-2">{{ session('info') }}</div>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle"></i> <strong>{{ session('error') }}</strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    <div class="row">
        @include('backend.pages.layouts.second-sidebar')
        <div class="col-md-9 export_pdf_report" id="mainContent">
            <div class="row mb-2 pdf-content">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-1">
                            <h4 class="card-title mb-0 instagram_connected">Instagram Integration - Connected</h4>
                            <div class="ms-auto">
                                <a href="{{ route('facebook.index') }}" class="btn btn-outline-primary">
                                    <i class="fab fa-facebook"></i> Back to Facebook
                                </a>
                            </div>
                            <button id="downloadPdf" class="btn btn-outline-primary pdf-download-btn no-print">
                                <i class="bx bx-download"></i> Download PDF Report
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="pdf-container">
                                <div class="pdf-section">
                                    <div class="pdf-header">
                                        <div class="pdf-header pdf-only">
                                            <div class="header-content" style="box-shadow:  0 15px 13px -7px rgba(0, 0, 0, 0.2); padding: 10px; margin-bottom: 20px;">
                                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                                    <div>
                                                        <h1 style="font-size: 30px; color: #000000; margin: 0; font-weight: bold;">Instagram Report</h1>
                                                        <p style="font-size: 18px; color: #000000; margin: 0px 0 0 0;">
                                                            Prepared by Wizards Next LLP | Dated: {{ \Carbon\Carbon::now()->format('d/m/Y') }}
                                                        </p>
                                                        <p style="font-size: 18px; color: #000000; margin: 0px 0 0 0;">
                                                            For the duration of:
                                                            <span id="report-date">

                                                            </span>
                                                        </p>
                                                    </div>
                                                    <div style="text-align: right;">
                                                        <img src="{{ asset('backend/assets/logo.png') }}" style="width:177px; height:45px;" class="logo-lg" alt="logo light" loading="lazy">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="instagram-profile-section">
                                        <div class="d-flex align-items-start mb-2">
                                            <img src="{{ $instagram['profile_picture_url'] ?? '' }}" width="100" height="100" class="me-3" alt="Profile">
                                            <div>
                                                <h3 class="mb-1 fw-bold">{{ $instagram['name'] ?? '' }}</h3>
                                                <p class="mb-1">{{ $instagram['username'] ?? '' }}</p>
                                                <p class="mb-2">{!! nl2br(e($instagram['biography'] ?? '')) !!}</p>
                                                <div class="d-flex gap-4">
                                                    <span><strong>{{ number_format($instagram['media_count'] ?? 0) }}</strong> posts</span>
                                                    <span><strong>{{ number_format($instagram['followers_count'] ?? 0) }}</strong> followers</span>
                                                    <span><strong>{{ number_format($instagram['follows_count'] ?? 0) }}</strong> following</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mandate-section">
                                        <div class="reach-views-section">
                                            <div class="row">
                                                <div class="col-md-6 mb-sm-1 mb-md-1 mb-lg-5 mb-xl-0 col-6 pe-xl-0 ps-xl-0">
                                                    <div id="reach-component">
                                                        @php
                                                            $reachData = $instagram['reach_data'] ?? null;
                                                        @endphp
                                                        @include('backend.pages.instagram.component.reach', ['reach' => $reachData])
                                                    </div>
                                                </div>
                                                <!-----VIEWS---->
                                                <div class="col-md-6 mb-sm-1 mb-md-1 mb-lg-5 mb-xl-0 col-6 pe-xl-0 ps-xl-0">
                                                    <div id="view-component">
                                                        @php
                                                            $viewData = $instagram['reach_data'] ?? null;
                                                        @endphp
                                                        @include('backend.pages.instagram.component.view', ['view' => $viewData])
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="reach-per-day-graph-section">
                                            @include('backend.pages.instagram.component.profile-reach-perday-graph')
                                        </div>
                                        <div class="followers-section">
                                            <div id="profile-followers-component">
                                                @php
                                                    $followersData = $instagram['followers_data']  ?? null;
                                                @endphp
                                                @include('backend.pages.instagram.component.profile-followers', ['followersData' => $followersData])
                                            </div>
                                        </div>
                                        <div class="total-views-section-graphs mt-2">
                                            @include('backend.pages.instagram.component.total-view-graph')
                                        </div>
                                        <div class="view-by-followers-type mt-2">
                                            @include('backend.pages.instagram.component.view-by-followers-and-non-follower')
                                        </div>
                                        <div class="post-reels-section mt-2">
                                            <div class="row">
                                                @include('backend.pages.instagram.component.post-reels')
                                            </div>
                                        </div>
                                        <div class="content-interaction mt-2">
                                            @include('backend.pages.instagram.component.total-interactions')
                                        </div>
                                        <div class="total-interaction-by-table mt-2">
                                            <div class="row total-interaction-by-table-box">
                                                @include('backend.pages.instagram.component.total-interactions-by-l-c-save-share-reposts')
                                                @include('backend.pages.instagram.component.total-interactions-by-media-type')
                                            </div>
                                        </div>
                                        <div class="profile-visit mt-2">
                                            @include('backend.pages.instagram.component.profile-visits')
                                        </div>
                                        <div class="engagement-section mt-2">
                                            @include('backend.pages.instagram.component.profile-engagement')
                                        </div>
                                        <div class="top-ten-city-audience mt-2">
                                            @include('backend.pages.instagram.component.top-cities-audience-graphs')
                                        </div>
                                        <div class="audience-by-age-group mt-2">
                                            @include('backend.pages.instagram.component.audience-by-age-group')
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
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
<script>
    window.insta_reach_pdf_url = "{{ route('instagram.reach.pdf', $instagram['id']) }}";
    window.insta_view_pdf_url = "{{ route('instagram.view.pdf', $instagram['id']) }}";
    window.insta_profile_reach_graphs_pdf_url = "{{ route('instagram.profile-reach-graphs.pdf', $instagram['id']) }}";
    window.insta_profile_follow_unfollow_pdf_url = "{{ route('insta.profile-follow-unfollow.pdf', $instagram['id']) }}";
    window.insta_view_graphs_media_type_pdf_url = "{{ route('insta.view-graphs-media-type.pdf', $instagram['id']) }}";


</script>
<script src="{{ asset('backend/assets/js/pages/insta-pdf/insta-reach.js') }}?v={{ time() }}"></script>
<script src="{{ asset('backend/assets/js/pages/insta-pdf/insta-view.js') }}?v={{ time() }}"></script>
<script src="{{ asset('backend/assets/js/pages/insta-pdf/insta-profile-reach-graphs.js') }}?v={{ time() }}"></script>
<script src="{{ asset('backend/assets/js/pages/insta-pdf/insta-profile-follow-unfollow.js') }}?v={{ time() }}"></script>
<script src="{{ asset('backend/assets/js/pages/insta-pdf/insta-view-graphs-media-type.js') }}?v={{ time() }}"></script>
<script>    
    $(document).ready(function() {
        const id = "{{ $instagram['id'] }}";
        const defaultStart = moment().subtract(28, 'days');
        const defaultEnd = moment().subtract(1, 'days');
        $('.daterange').daterangepicker({
            opens: 'right',
            startDate: defaultStart,
            endDate: defaultEnd,
            maxDate: moment().subtract(1, 'days'),
            dateLimit: {
                days: 27
            },
            ranges: {
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(7, 'days'), moment().subtract(1, 'days')],
                'Last 15 Days': [moment().subtract(15, 'days'), moment().subtract(1, 'days')],
                'Last 28 Days': [moment().subtract(28, 'days'), moment().subtract(1, 'days')],
            },
            autoUpdateInput: true,
            locale: {
                format: 'YYYY-MM-DD',
                cancelLabel: 'Clear',
            },
            alwaysShowCalendars: true,
            showDropdowns: true,
        }, function(start, end) {
            $('.daterange').val(`${start.format('YYYY-MM-DD')} - ${end.format('YYYY-MM-DD')}`);
        });

        $('.daterange').on('apply.daterangepicker', function(ev, picker) {
            const startDate = picker.startDate;
            const endDate = picker.endDate;
            const totalDays = endDate.diff(startDate, 'days') + 1;
            if (totalDays > 28) {
                alert('You can only select up to 28 days (inclusive). Please reduce the range.');
                picker.setEndDate(startDate.clone().add(28, 'days'));
                return;
            }

            const start = startDate.format('YYYY-MM-DD');
            const end = endDate.format('YYYY-MM-DD');

            $(this).val(`${start} - ${end}`);
            generateInstaReachPDF(id, start, end);
            generateInstaViewPDF(id, start, end);
            profileReachGraph(id, start, end);
            instaProfileFollowUnfollowPDF(id, start, end);
            instaViewGraph(id, start, end);

        });

        $('.daterange').on('cancel.daterangepicker', function() {
            $(this).val('');
            const defaultStart = moment().subtract(28, 'days').format('YYYY-MM-DD');
            const defaultEnd = moment().subtract(1, 'days').format('YYYY-MM-DD');
            generateInstaReachPDF(id, defaultStart, defaultEnd);
            generateInstaViewPDF(id, defaultStart, defaultEnd);
            profileReachGraph(id, defaultStart, defaultEnd);
            instaProfileFollowUnfollowPDF(id, defaultStart, defaultEnd);
            instaViewGraph(id, defaultStart, defaultEnd);

        });

        $('.daterange').val(`${defaultStart.format('YYYY-MM-DD')} - ${defaultEnd.format('YYYY-MM-DD')}`);
        generateInstaReachPDF(id, defaultStart.format('YYYY-MM-DD'), defaultEnd.format('YYYY-MM-DD'));
        generateInstaViewPDF(id, defaultStart.format('YYYY-MM-DD'), defaultEnd.format('YYYY-MM-DD'));
        profileReachGraph(id, defaultStart.format('YYYY-MM-DD'), defaultEnd.format('YYYY-MM-DD'));
        instaProfileFollowUnfollowPDF(id, defaultStart.format('YYYY-MM-DD'), defaultEnd.format('YYYY-MM-DD'));
        instaViewGraph(id, defaultStart.format('YYYY-MM-DD'), defaultEnd.format('YYYY-MM-DD'));
        

    });
</script>

@endpush