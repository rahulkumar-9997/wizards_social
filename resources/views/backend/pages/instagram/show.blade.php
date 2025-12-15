@extends('backend.pages.layouts.master')
@section('title', 'Instagram Dashboard')
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous" />

<style>
    .pdf-only {
        display: none;
    }

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
        padding: 4px;
    }



    .stats-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        text-align: center;
    }

    .account-enga {
        flex: 1;
        padding: 4px;
    }

    .account-enga h4 {
        font-size: 1.4rem;
        font-weight: 700;
    }

    .account-enga p {
        font-size: 0.9rem;
        color: #6c757d;
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

    .metrics-table th {
        background: linear-gradient(135deg, #111, #333);
        color: #fff;
        font-weight: 600;
        /* text-transform: uppercase; */
        /* font-size: 13px; */
        padding: 5px;
    }

    .metrics-table td {
        vertical-align: middle;
        font-size: 16px;
        padding: 4px;
    }

    .metrics-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .metric-section-header {
        background: #222;
        color: #fff;
        text-align: center;
        font-size: 16px;
        font-weight: 500;
        letter-spacing: .5px;
    }

    .highlight {
        font-weight: 600;
        color: #007bff;
    }

    .page-break {
        page-break-before: always;
        break-before: page;
        height: 40px;
        background: transparent !important;
    }

    .location-table td {
        padding: 4px;
    }
</style>

@endpush
@section('main-content')
<div class="container-fluid">
    <div class="row">
        @include('backend.pages.layouts.second-sidebar', [
        'selectedInstagramId' => $instagram['id'] ?? null
        ])
        <div class="col-xl-9 export_pdf_report">
            <div class="pdf-header pdf-only" style="display: none;">
                <div class="header-content" style="padding: 10px; border-bottom: 5px solid #fd7e03; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h1 style="font-size: 30px; color: #000000; margin: 0; font-weight: bold;">Instagram Report</h1>
                            <p style="font-size: 18px; color: #000000; margin: 5px 0 0 0;">For the Date Range of:
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
            <div class="pdf-footer pdf-only" style="display: none;">
                <div class="header-content" style="padding: 10px; border-top: 5px solid #fd7e03; margin-top: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="margin-right: 5px;">
                                <img src="{{ asset('backend/assets/phone-icon.png') }}">
                            </div>
                            <div>
                                <h4 style="font-size: 18px; color: #000000; margin: 0px 0 0 0;">
                                    +91-7339474554
                                </h4>
                            </div>

                        </div>
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="margin-right: 5px;">
                                    <img src="{{ asset('backend/assets/location-icon.png') }}">
                                </div>
                                <div>
                                    <h4 style="font-size: 16px; color: #000000; margin: 0px 0 0 0; line-height: 20px;">
                                        Wizards Next LLP, Sigra Mahmoorganj Road,<br> Near
                                        Santoor Hotel, Varanasi, 221010
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-2 pdf-content">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-1">
                            <h4 class="card-title mb-0 instagram_connected">Instagram Integration - Connected</h4>
                            <button id="downloadPdf" class="btn btn-outline-primary pdf-download-btn no-print">
                                <i class="bx bx-download"></i> Download PDF Report
                            </button>
                        </div>
                        <div class="card-body d-flex align-items-center">
                            <img src="{{ $instagram['profile_picture_url'] ?? '' }}" width="100" height="100" class="me-3" alt="Profile">
                            <div>
                                <h3 class="mb-1 fw-bold">{{ $instagram['name'] ?? '' }}</h3>
                                <p class="text-muted mb-1">{{ $instagram['username'] ?? '' }}</p>
                                <p class="mb-2">{!! nl2br(e($instagram['biography'] ?? '')) !!}</p>
                                <div class="d-flex gap-4">
                                    <span><strong>{{ number_format($instagram['media_count'] ?? 0) }}</strong> posts</span>
                                    <span><strong>{{ number_format($instagram['followers_count'] ?? 0) }}</strong> followers</span>
                                    <span><strong>{{ number_format($instagram['follows_count'] ?? 0) }}</strong> following</span>
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

                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xxl-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-1">
                            <h5 class="card-title mb-0">
                                Total Views
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
            <div class="page-break"></div>
            <!--Total interaction section-->
            <div class="row mb-2">
                <div class="col-xxl-12">
                    <div id="total_interactions_dashboard">

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
                <div class="page-break"></div>
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
            </div>
            <div class="page-break"></div>
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
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <script>
        window.instagram_id = "{{ $instagram['id'] }}";
        window.instagramTopLocationUrl = "{{ route('instagram.top.location', $instagram['id']) }}";
        window.instagramAudienceAgeUrl = "{{ route('instagram.audienceAgeGender', $instagram['id']) }}";
        window.instagramFetchPostUrl = "{{ route('instagram.fetch.post', $instagram['id']) }}";
        window.instagramFetchReachDaysWise = "{{ route('instagram.fetch.reach-day-wise', $instagram['id']) }}";
        window.instagramFetchViewDaysWise = "{{ route('instagram.fetch.view-day-wise', $instagram['id']) }}";
        window.INSTAGRAM_BASE_URL = "{{ url('/instagram') }}";
        const instagramFetchUrl = "{{ route('instagram.fetch.html', $instagram['id']) }}";
        window.facebook_base_url = "{{ url('facebook-summary') }}";
    </script>

    <script src="{{ asset('backend/assets/js/pages/instagram-top-location.js') }}"></script>
    <script src="{{ asset('backend/assets/js/pages/instagram-audience-age.js') }}"></script>
    <script src="{{ asset('backend/assets/js/pages/instagram-post.js') }}"></script>
    <script src="{{ asset('backend/assets/js/pages/reach-days-wize-graphs.js') }}"></script>
    <script src="{{ asset('backend/assets/js/pages/instagram-view-days-wise.js') }}"></script>
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
                loadInstagramPostData(id, start, end);
                loadReachGraph(id, start, end);
                loadViewGraph(id, start, end);
                loadInstagramData(id, start, end);

            });

            $('.daterange').on('cancel.daterangepicker', function() {
                $(this).val('');
                const defaultStart = moment().subtract(28, 'days').format('YYYY-MM-DD');
                const defaultEnd = moment().subtract(1, 'days').format('YYYY-MM-DD');
                loadInstagramPostData(id, defaultStart, defaultEnd);
                loadReachGraph(id, defaultStart, defaultEnd);
                loadViewGraph(id, defaultStart, defaultEnd);
                loadInstagramData(id, defaultStart, defaultEnd);

            });

            $('.daterange').val(`${defaultStart.format('YYYY-MM-DD')} - ${defaultEnd.format('YYYY-MM-DD')}`);
            loadInstagramPostData(id, defaultStart.format('YYYY-MM-DD'), defaultEnd.format('YYYY-MM-DD'));
            loadReachGraph(id, defaultStart.format('YYYY-MM-DD'), defaultEnd.format('YYYY-MM-DD'));
            loadViewGraph(id, defaultStart.format('YYYY-MM-DD'), defaultEnd.format('YYYY-MM-DD'));
            loadInstagramData(id, defaultStart.format('YYYY-MM-DD'), defaultEnd.format('YYYY-MM-DD'));

        });

        function loadInstagramData(accountId, startDate, endDate) {
            const loadingHtml = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading performance data...</p>
            </div>`;
            $('#insta_face_dashboard').html(loadingHtml);
            $('#total_interactions_dashboard').html(loadingHtml);
            $.ajax({
                url: instagramFetchUrl,
                type: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(res) {
                    if (res.success) {
                        $('#insta_face_dashboard').html(res.html);
                        $('#total_interactions_dashboard').html(res.total_interaction_other);
                        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                            new bootstrap.Tooltip(tooltipTriggerEl);
                        });
                    } else {
                        $('#insta_face_dashboard').html(`<div class="alert alert-danger">${res.message}</div>`);
                        $('#total_interactions_dashboard').html(`<div class="alert alert-danger">${res.message}</div>`);
                    }
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.error || 'Error fetching data';
                    $('#insta_face_dashboard').html(`<div class="alert alert-danger">${errorMessage}</div>`);
                    $('#total_interactions_dashboard').html(`<div class="alert alert-danger">${errorMessage}</div>`);
                }
            });
        }
    </script>

    <!--generate a pdf file-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <script>
/* ================= FOOTER IMAGE HELPER ================= */
async function getFooterImage() {
    const footer = document.querySelector('.pdf-footer');
    if (!footer) return null;
    footer.style.display = 'block';
    const canvas = await html2canvas(footer, {
        scale: window.devicePixelRatio * 2,
        backgroundColor: '#ffffff',
        useCORS: true
    });
    footer.style.display = 'none';
    return canvas.toDataURL('image/png');
}

/* ================= DATE FORMAT HELPER ================= */
function formatDateRange(range) {
    if (!range) return 'N/A';
    const parts = range.split('-').map(p => p.trim());
    if (parts.length < 4) return range;
    const startDate = `${parts[0]}-${parts[1]}-${parts[2]}`;
    const endDate   = `${parts[3]}-${parts[4]}-${parts[5]}`;
    const months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
    const start = new Date(startDate);
    const end   = new Date(endDate);
    const startStr = `${start.getDate().toString().padStart(2,'0')}-${months[start.getMonth()]}-${start.getFullYear().toString().slice(2)}`;
    const endStr   = `${end.getDate().toString().padStart(2,'0')}-${months[end.getMonth()]}-${end.getFullYear().toString().slice(2)}`;
    return `${startStr} to ${endStr}`;
}


/* ================= MAIN PDF SCRIPT ================= */
document.addEventListener('DOMContentLoaded', function() {
    const downloadBtn = document.getElementById('downloadPdf');
    const dateInput = document.querySelector('.daterange');
    const reportDateSpan = document.getElementById('report-date');
    downloadBtn.addEventListener('click', async function() {
        const button = this;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="bx bx-loader bx-spin"></i> Generating PDF...';
        button.disabled = true;
        const element = document.querySelector('.export_pdf_report');
        const pdfHeader = document.querySelector('.pdf-header');
        if (pdfHeader && dateInput && reportDateSpan) {
            reportDateSpan.textContent = formatDateRange(dateInput.value);
        }
        if (pdfHeader) pdfHeader.style.display = 'block';
        const allVideos = document.querySelectorAll('video.video-section');
        allVideos.forEach(video => {
            video.style.display = 'none';
            video.style.visibility = 'hidden';
            video.style.position = 'absolute';
        });
        const allPdfImgs = document.querySelectorAll('img.pdf-img');
        allPdfImgs.forEach(img => {
            img.style.display = 'block';
            img.style.visibility = 'visible';
        });

        if (!element) {
            alert('Export element not found');
            button.innerHTML = originalText;
            button.disabled = false;
            return;
        }

        const originalStyles = {
            overflow: element.style.overflow,
            position: element.style.position
        };

        element.style.overflow = 'visible';
        element.style.position = 'relative';

        try {
            const footerImg = await getFooterImage();
            const canvas = await html2canvas(element, {
                scale: window.devicePixelRatio * 3,
                useCORS: true,
                backgroundColor: '#ffffff',
                letterRendering: true,
                imageTimeout: 0,
                allowTaint: false,
                logging: false,
                onclone: function(clonedDoc) {
                    const clonedElement = clonedDoc.querySelector('.export_pdf_report');
                    const clonedHeader = clonedDoc.querySelector('.pdf-header');
                    if (clonedHeader) clonedHeader.style.display = 'block';
                    const clonedReportDate = clonedDoc.querySelector('#report-date');
                    if (clonedReportDate && dateInput) {
                        clonedReportDate.textContent = formatDateRange(dateInput.value);
                    }
                    clonedElement.querySelectorAll('video.video-section').forEach(v => v.remove());
                    clonedElement.querySelectorAll('img.pdf-img').forEach(img => {
                        img.style.display = 'block';
                        img.style.visibility = 'visible';
                        img.style.width = '50px';
                        img.style.height = '50px';
                    });
                    clonedElement.querySelectorAll('img.real-image').forEach(img => {
                        img.style.display = 'none';
                    });
                    clonedElement.querySelectorAll(
                        '.pdf-download-btn, .btn, .btn-outline-primary, .btn-outline-secondary, .filter-box, .performance_data, .instagram_connected, #viewDateRange, #timeframe, #ageTimeframe, #reachDateRange, .showing-post'
                    ).forEach(el => el.style.display = 'none');
                    clonedElement.querySelectorAll('tr.post-row').forEach(row => {
                        row.style.height = '50px';
                    });
                    clonedElement.querySelectorAll('tr.post-row td').forEach(cell => {
                        cell.style.height = '50px';
                        cell.style.verticalAlign = 'middle';
                    });
                    clonedElement.querySelectorAll('tr.post-row td:nth-child(2)').forEach(cell => {
                        cell.style.padding = '3px';
                    });
                    clonedElement.querySelectorAll('.page-break').forEach(pb => {
                        pb.style.display = 'block';
                        pb.style.height = '430px';
                        pb.style.background = 'transparent';
                    });
                }
            });
            element.style.overflow = originalStyles.overflow;
            element.style.position = originalStyles.position;
            /* ================= CREATE PDF ================= */
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jspdf.jsPDF('p', 'mm', 'a4');
            const pageWidth = 210;
            const pageHeight = 297;
            const margin = 10;
            const footerHeight = 15;
            const imgWidth = pageWidth - margin * 2;
            const imgHeight = canvas.height * imgWidth / canvas.width;
            let heightLeft = imgHeight;
            let position = margin;
            pdf.addImage(imgData, 'PNG', margin, position, imgWidth, imgHeight);
            if (footerImg) {
                pdf.addImage(footerImg, 'PNG', margin, pageHeight - footerHeight, imgWidth, footerHeight);
            }
            heightLeft -= (pageHeight - footerHeight);
            while (heightLeft > 0) {
                pdf.addPage();
                position = heightLeft - imgHeight + margin;
                pdf.addImage(imgData, 'PNG', margin, position, imgWidth, imgHeight);
                if (footerImg) {
                    pdf.addImage(footerImg, 'PNG', margin, pageHeight - footerHeight, imgWidth, footerHeight);
                }
                heightLeft -= (pageHeight - footerHeight);
            }
            pdf.save('Instagram_Dashboard_Report_HD.pdf');
        } catch (error) {
            console.error('PDF error:', error);
            alert('Error generating PDF');
        }
        /* ================= RESTORE UI ================= */
        if (pdfHeader) pdfHeader.style.display = 'none';
        allVideos.forEach(video => {
            video.style.display = '';
            video.style.visibility = '';
            video.style.position = '';
        });
        allPdfImgs.forEach(img => {
            img.style.display = 'none';
            img.style.visibility = 'hidden';
        });
        button.innerHTML = originalText;
        button.disabled = false;
    });
});
</script>




    @endpush