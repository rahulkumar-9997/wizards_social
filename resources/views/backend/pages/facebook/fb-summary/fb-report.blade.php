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
                    <div id="facebook_html_data">
                        
                    </div>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-xxl-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-1">
                            <h4 class="card-title mb-0">
                                Lifetime Follows by City
                                <i id="audienceByCitiesTitle"
                                    class="bx bx-question-mark text-primary"
                                    style="cursor: pointer; font-size: 18px;"
                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                    data-bs-custom-class="warning-tooltip">
                                </i>
                            </h4>                            
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
        const facebookFetchUrl = "{{ route('facebook.fetch.html', $facebookBusinessOrProfile['id']) }}";
    </script>
    <script>
        $(document).ready(function() {
            const id = "{{ $facebookBusinessOrProfile['id'] }}";
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
                loadFacebookData(id, start, end);
            });

            $('.daterange').on('cancel.daterangepicker', function() {
                $(this).val('');
                const defaultStart = moment().subtract(28, 'days').format('YYYY-MM-DD');
                const defaultEnd = moment().subtract(1, 'days').format('YYYY-MM-DD');
                loadFacebookData(id, defaultStart, defaultEnd);
            });

            $('.daterange').val(`${defaultStart.format('YYYY-MM-DD')} - ${defaultEnd.format('YYYY-MM-DD')}`);
            loadFacebookData(id, defaultStart.format('YYYY-MM-DD'), defaultEnd.format('YYYY-MM-DD'));
        });


        function loadFacebookData(accountId, startDate, endDate) {
            const loadingHtml = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading performance data...</p>
            </div>`;
            $('#facebook_html_data').html(loadingHtml);
            $.ajax({
                url: facebookFetchUrl,
                type: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(res) {
                    if (res.success) {
                        $('#facebook_html_data').html(res.html);
                        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                            new bootstrap.Tooltip(tooltipTriggerEl);
                        });
                    } else {
                        $('#facebook_html_data').html(`<div class="alert alert-danger">${res.message}</div>`);
                    }
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.error || 'Error fetching data';
                    $('#facebook_html_data').html(`<div class="alert alert-danger">${errorMessage}</div>`);
                }
            }); 
        }
    </script>
    
@endpush