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
                                                    <div class="mandate-section">
                                                        <div class="mandate-header-top">
                                                            <h3 class="mandate-title">
                                                                REACH
                                                                <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="">
                                                                </i>
                                                            </h3>
                                                            <p style="text-align: justify;">
                                                                It shows how many different people discovered
                                                                your profile, not how many times it was viewed.
                                                                Profile reach helps you understand your brand
                                                                visibility and audience interest. Higher profile
                                                                reach often means your content is attracting
                                                                new users to your page.
                                                            </p>
                                                        </div>
                                                        <div class="mandate-item">
                                                            <div class="mandate-item-header" style="box-shadow:  0 15px 13px -7px rgba(0, 0, 0, 0.2); padding: 10px; margin-bottom: 10px;">
                                                                <div class="d-flex justify-content-between align-items-center gap-1">
                                                                    <div class="mandate-item-title">
                                                                        <h5 style="margin-bottom: 0px;">Previous Month</h5>
                                                                        <div class="mandate-item-text">
                                                                            <h2 class="mb-0">51K</h2>
                                                                        </div>
                                                                    </div>

                                                                    <div class="mandate-item-title">
                                                                        <h5 style="margin-bottom: 0px;">Current Month</h5>
                                                                        <div class="mandate-item-text">
                                                                            <h1 class="mb-0">35.8K</h1>
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                            <div class="mandate-item-body">
                                                                <div class="d-flex justify-content-between align-items-center gap-1">
                                                                    <div class="mandate-item-title d-flex flex-wrap">
                                                                        <div class="col-custom-2">
                                                                            <h5 style="margin-bottom: 0px;">Paid</h5>
                                                                            <h3>
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-2">
                                                                            <h5 style="margin-bottom: 0px;">Organic</h5>
                                                                            <h3>
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-2">
                                                                            <h5 style="margin-bottom: 0px;">Follower</h5>
                                                                            <h3>
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-2">
                                                                            <h5 style="margin-bottom: 0px;">Non - Follower</h5>
                                                                            <h3>
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                    </div>
                                                                    <div class="mandate-item-arrow">
                                                                        <h4 style="margin-bottom: 5px; color: #e70000ff; font-size: 24px;">
                                                                            28.9%
                                                                        </h4>
                                                                        <div class="mandate-arrow-icon">
                                                                            <img src="{{ asset('backend/assets/red-arrow-down.png') }}" alt="Down Arrow" width="24" height="24">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-----VIEWS---->
                                                <div class="col-md-6 mb-sm-1 mb-md-1 mb-lg-5 mb-xl-0 col-6 pe-xl-0 ps-xl-0">
                                                    <div class="mandate-section">
                                                        <div class="mandate-header-top">
                                                            <h3 class="mandate-title">
                                                                VIEWS
                                                                <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="">
                                                                </i>
                                                            </h3>
                                                            <p style="text-align: justify;">
                                                                It shows how many different people discovered
                                                                your profile, not how many times it was viewed.
                                                                Profile reach helps you understand your brand
                                                                visibility and audience interest. Higher profile
                                                                reach often means your content is attracting
                                                                new users to your page.
                                                            </p>
                                                        </div>
                                                        <div class="mandate-item">
                                                            <div class="mandate-item-header" style="box-shadow:  0 15px 13px -7px rgba(0, 0, 0, 0.2); padding: 10px; margin-bottom: 10px;">
                                                                <div class="d-flex justify-content-between align-items-center gap-1">
                                                                    <div class="mandate-item-title">
                                                                        <h5 style="margin-bottom: 0px;">Previous Month</h5>
                                                                        <div class="mandate-item-text">
                                                                            <h2 class="mb-0">51K</h2>
                                                                        </div>
                                                                    </div>

                                                                    <div class="mandate-item-title">
                                                                        <h5 style="margin-bottom: 0px;">Current Month</h5>
                                                                        <div class="mandate-item-text">
                                                                            <h1 class="mb-0">35.8K</h1>
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                            <div class="mandate-item-body">
                                                                <div class="d-flex justify-content-between align-items-center gap-1">
                                                                    <div class="mandate-item-title d-flex flex-wrap">
                                                                        <div class="col-custom-2">
                                                                            <h5 style="margin-bottom: 0px;">Paid</h5>
                                                                            <h3>
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-2">
                                                                            <h5 style="margin-bottom: 0px;">Organic</h5>
                                                                            <h3>
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-2">
                                                                            <h5 style="margin-bottom: 0px;">Follower</h5>
                                                                            <h3>
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-2">
                                                                            <h5 style="margin-bottom: 0px;">Non - Follower</h5>
                                                                            <h3>
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                    </div>
                                                                    <div class="mandate-item-arrow">
                                                                        <h4 style="margin-bottom: 5px; color: #05ff16ff; font-size: 24px;">
                                                                            28.9%
                                                                        </h4>
                                                                        <div class="mandate-arrow-icon">
                                                                            <img src="{{ asset('backend/assets/green-arrow-up.png') }}" alt="Down Arrow" width="24" height="24">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="reach-per-day-graph-section">
                                            <div id="reachDaysContainer" class="mt-4">
                                                <img src="{{ asset('backend/assets/reach-per-day.png') }}" alt="Reach Per Day Graph" style="width: 100%; height: auto;">
                                            </div>
                                            <div class="pdf-other-title mt-1">
                                                <h3 class="mandate-title">
                                                    PER DAY REACH OF THE PROFILE
                                                    <i id="profileReachTitle" class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="danger-tooltip" data-bs-title="">
                                                    </i>
                                                </h3>
                                            </div>
                                        </div>
                                        <div class="followers-section">
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
                                                                                <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="">
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
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-3">
                                                                            <h5 style="margin-bottom: 0px;">Current Month</h5>
                                                                            <h3 class="follow-font">
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-3">
                                                                            <div class="mandate-item-arrow">
                                                                                <h4 style="margin-bottom: 5px; color: #e70000ff; font-size: 24px;">
                                                                                    28.9%
                                                                                </h4>
                                                                                <div class="mandate-arrow-icon">
                                                                                    <img src="http://localhost:8000/backend/assets/red-arrow-down.png" alt="Down Arrow" width="24" height="24">
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
                                                                                <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="" aria-describedby="tooltip549868">
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
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-3">
                                                                            <h5 style="margin-bottom: 0px;">Current Month</h5>
                                                                            <h3 class="follow-font">
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-3">
                                                                            <div class="mandate-item-arrow">
                                                                                <h4 style="margin-bottom: 5px; color: #e70000ff; font-size: 24px;">
                                                                                    28.9%
                                                                                </h4>
                                                                                <div class="mandate-arrow-icon">
                                                                                    <img src="http://localhost:8000/backend/assets/red-arrow-down.png" alt="Down Arrow" width="24" height="24">
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
                                        <div class="total-views-section-graphs mt-2">
                                            <h3 class="text-center">
                                                TOTAL VIEWS
                                                <i id="viewDateRangeTitle" class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="">
                                                </i>
                                            </h3>
                                            <div id="viewDaysContainer" class="mt-2">
                                                <img src="{{ asset('backend/assets/views-by-content-type.png') }}" alt="Views by Content Type Graph" style="width: 100%; height: auto;">
                                            </div>
                                        </div>
                                        <div class="view-by-followers-type mt-2">
                                            <div class="pdf-se-title">
                                                <h3 class="text-center">
                                                    VIEW BY FOLLOWERS & NON FOLLOWERS
                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="">
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
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-3">
                                                                            <h5 style="margin-bottom: 0px;">Current Month</h5>
                                                                            <h3 class="follow-font">
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-3">
                                                                            <div class="mandate-item-arrow">
                                                                                <h4 style="margin-bottom: 5px; color: #e70000ff; font-size: 24px;">
                                                                                    28.9%
                                                                                </h4>
                                                                                <div class="mandate-arrow-icon">
                                                                                    <img src="http://localhost:8000/backend/assets/red-arrow-down.png" alt="Down Arrow" width="24" height="24">
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
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-3">
                                                                            <h5 style="margin-bottom: 0px;">Current Month</h5>
                                                                            <h3 class="follow-font">
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-3">
                                                                            <div class="mandate-item-arrow">
                                                                                <h4 style="margin-bottom: 5px; color: #e70000ff; font-size: 24px;">
                                                                                    28.9%
                                                                                </h4>
                                                                                <div class="mandate-arrow-icon">
                                                                                    <img src="http://localhost:8000/backend/assets/red-arrow-down.png" alt="Down Arrow" width="24" height="24">
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
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-3">
                                                                            <h5 style="margin-bottom: 0px;">Current Month</h5>
                                                                            <h3 class="follow-font">
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-3">
                                                                            <div class="mandate-item-arrow">
                                                                                <h4 style="margin-bottom: 5px; color: #e70000ff; font-size: 24px;">
                                                                                    28.9%
                                                                                </h4>
                                                                                <div class="mandate-arrow-icon">
                                                                                    <img src="http://localhost:8000/backend/assets/red-arrow-down.png" alt="Down Arrow" width="24" height="24">
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
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-3">
                                                                            <h5 style="margin-bottom: 0px;">Current Month</h5>
                                                                            <h3 class="follow-font">
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-3">
                                                                            <div class="mandate-item-arrow">
                                                                                <h4 style="margin-bottom: 5px; color: #e70000ff; font-size: 24px;">
                                                                                    28.9%
                                                                                </h4>
                                                                                <div class="mandate-arrow-icon">
                                                                                    <img src="http://localhost:8000/backend/assets/red-arrow-down.png" alt="Down Arrow" width="24" height="24">
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
                                        <div class="content-interaction mt-2">
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
                                                                                <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="">
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
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-3">
                                                                            <h5 style="margin-bottom: 0px;">Current Month</h5>
                                                                            <h3 class="follow-font">
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-3">
                                                                            <div class="mandate-item-arrow">
                                                                                <h4 style="margin-bottom: 5px; color: #e70000ff; font-size: 24px;">
                                                                                    28.9%
                                                                                </h4>
                                                                                <div class="mandate-arrow-icon">
                                                                                    <img src="http://localhost:8000/backend/assets/red-arrow-down.png" alt="Down Arrow" width="24" height="24">
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
                                        </div>
                                        <div class="total-interaction-by-table mt-2">
                                            <div class="row total-interaction-by-table-box">
                                                <div class="col-lg-6">
                                                    <div class="col-lg-12 mb-2">
                                                        <h5 class="card-title mb-0">Total Interactions by Likes, Comments, Saves, Shares, Reposts </h5>
                                                    </div>
                                                    <table class="table table-bordered table-sm interactions-table">
                                                        <tbody>
                                                            <tr>
                                                                <td class="bg-black text-light">Previous Month</td>
                                                                <td class="bg-black text-light">Current Month</td>
                                                            </tr>
                                                            <tr>
                                                                <!-- Previous Month -->
                                                                <td>
                                                                    <table class="table table-sm interface-table">
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
                                                                                    <h4 class="mb-0">11.3K</h4>
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
                                                                                    <h4 class="mb-0">124</h4>
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
                                                                                    <h4 class="mb-0">1.3K</h4>
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
                                                                                    <h4 class="mb-0">1.8K</h4>
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
                                                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of likes on your posts, reels and videos.">
                                                                                        </i>
                                                                                    </h4>
                                                                                </td>
                                                                                <td>
                                                                                    <h4 class="mb-0">
                                                                                        5.3K
                                                                                        <small class="text-danger">
                                                                                            ▼ 53.36%</small>
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
                                                                                        63
                                                                                        <small class="text-danger">
                                                                                            ▼ 49.19%</small>
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
                                                                                        779
                                                                                        <small class="text-danger">
                                                                                            ▼ 38.42%</small>
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
                                                                                        690
                                                                                        <small class="text-danger">
                                                                                            ▼ 61.34%</small>
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
                                                    <table class="table table-bordered table-sm mb-2 interactions-table">
                                                        <tbody>
                                                            <tr>
                                                                <td class="bg-black text-light">Previous Month</td>
                                                                <td class="bg-black text-light">Current Month</td>
                                                            </tr>
                                                            <tr>
                                                                <!-- Previous Month -->
                                                                <td>
                                                                    <table class="table table-sm interface-table">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td>
                                                                                    <h4 class="mb-0">Post
                                                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                        </i>
                                                                                    </h4>
                                                                                </td>
                                                                                <td>
                                                                                    <h4 class="mb-0">372</h4>
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
                                                                                    <h4 class="mb-0">8.7K</h4>
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
                                                                                    <h4 class="mb-0">7.4K</h4>
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
                                                                                    <h4 class="mb-0">49</h4>
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
                                                                                    <h4 class="mb-0">Post
                                                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                        </i>
                                                                                    </h4>
                                                                                </td>
                                                                                <td>
                                                                                    <h4 class="mb-0">
                                                                                        295
                                                                                        <small class="text-danger">▼ 20.7%</small>
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
                                                                                        3.3K
                                                                                        <small class="text-danger">▼ 61.92%</small>
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
                                                                                        4K
                                                                                        <small class="text-danger">▼ 46.05%</small>
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
                                                                                        <small class="text-danger">▼ 6.12%</small>
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
                                        <div class="profile-visit mt-2">
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
                                                                                <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="" aria-describedby="tooltip511529">
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
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-3">
                                                                            <h5 style="margin-bottom: 0px;">Current Month</h5>
                                                                            <h3 class="follow-font">
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-3">
                                                                            <div class="mandate-item-arrow">
                                                                                <h4 style="margin-bottom: 5px; color: #e70000ff; font-size: 24px;">
                                                                                    28.9%
                                                                                </h4>
                                                                                <div class="mandate-arrow-icon">
                                                                                    <img src="http://localhost:8000/backend/assets/red-arrow-down.png" alt="Down Arrow" width="24" height="24">
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
                                        </div>
                                        <div class="engagement-section mt-2">
                                            <div class="row align-items-center">
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
                                                <div class="col-md-7 mb-sm-1 mb-md-1 mb-lg-5 mb-xl-1 col-6 pe-xl-0 ps-xl-0 mb-2">
                                                    <div class="mandate-section">
                                                        <div class="mandate-item">
                                                            <div class="mandate-item-header" style="box-shadow:  0 15px 13px -7px rgba(0, 0, 0, 0.2); padding: 10px; margin-bottom: 10px;">
                                                                <div class="text-center">
                                                                    <div class="mandate-item-title">
                                                                        <div class="mandate-item-text">
                                                                            <h2 class="mb-0">
                                                                                ENGAGEMENT
                                                                                <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="">
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
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-3">
                                                                            <h5 style="margin-bottom: 0px;">Current Month</h5>
                                                                            <h3 class="follow-font">
                                                                                51K
                                                                            </h3>
                                                                        </div>
                                                                        <div class="col-custom-3">
                                                                            <div class="mandate-item-arrow">
                                                                                <h4 style="margin-bottom: 5px; color: #e70000ff; font-size: 24px;">
                                                                                    28.9%
                                                                                </h4>
                                                                                <div class="mandate-arrow-icon">
                                                                                    <img src="http://localhost:8000/backend/assets/red-arrow-down.png" alt="Down Arrow" width="24" height="24">
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
                                        <div class="top-ten-city-audience mt-2">
                                            <h3 class="text-center">
                                               TOP 10 CITIES AUDIENCE
                                               <i id="audienceByCitiesTitle" class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="warning-tooltip" data-bs-title="">
                                            </i>
                                            </h3>
                                            <div id="geolocationContainer" class="mt-2">
                                                <img src="{{ asset('backend/assets/views-by-content-type.png') }}" alt="Views by Content Type Graph" style="width: 100%; height: auto;">
                                            </div>
                                        </div>
                                        <div class="top-ten-city-audience mt-2">
                                            <h3 class="text-center">
                                               AUDIENCE BY AGE GROUP
                                               <i id="audienceByAgeGroup" class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="info-tooltip" data-bs-title="" aria-describedby="tooltip610478">
                                                </i>
                                            </h3>
                                            <div id="audienceAgeGroupContainer" class="mt-2">
                                                <img src="{{ asset('backend/assets/views-by-content-type.png') }}" alt="Views by Content Type Graph" style="width: 100%; height: auto;">
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
</div>
@endsection

@push('scripts')

@endpush