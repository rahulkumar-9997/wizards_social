@extends('backend.pages.layouts.master')
@section('title', 'Facebook Ads')
@push('styles')

@endpush
@section('main-content')
<div class="container-fluid">
    <div class="row">
        @include('backend.pages.layouts.second-sidebar')

        <div class="col-md-9 export_pdf_report"  id="mainContent">
            <div class="ads-section">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center gap-1">
                        <div class="text-white">
                            <h4 class="card-title mb-0">Ads</h4>
                        </div>

                        <div class="d-flex gap-2">
                            <!-- <button class="btn btn-outline-light" id="customizeColumnsBtn">
                                <i class="fas fa-columns me-1"></i> Customize Columns
                            </button> -->

                            <div class="ads-select-option" style="width: 350px;">
                                @if(!empty($adAccount['data']) && count($adAccount['data']) > 0)
                                <select class="form-control" id="ad_select"
                                    name="ad_account_id"
                                    data-choices
                                    data-placeholder="Select Facebook Ad Account"
                                    required>
                                    @foreach($adAccount['data'] as $adsRow)
                                    <option value="{{ $adsRow['id'] }}" {{ $loop->first ? 'selected' : '' }}>
                                        {{ $adsRow['name'] }} ({{ $adsRow['id'] }})
                                    </option>
                                    @endforeach
                                </select>
                                @else
                                <div class="text-muted small mt-2">No Ad Accounts Found</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div id="ads-summary-container" class="mt-3 text-center">
                            <div class="text-muted">Select an Ad Account to load Ads...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Customize Columns Modal -->
<div class="modal fade" id="customizeColumnsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Customise columns</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="ads-customize-coloumn">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-1">
                                <label class="form-label">Show or hide columns</label>
                                <input type="text" class="form-control" placeholder="Search" id="columnSearch">
                            </div>
                            <div class="section-group">
                                <h5 class="section-title">Ad details</h5>
                                <div class="column-list">
                                    @foreach(['title', 'status', 'start_date', 'end_date', 'budget'] as $column)
                                    <div class="form-check">
                                        <input class="form-check-input column-checkbox" type="checkbox"
                                            value="{{ $column }}" id="col_{{ $column }}" checked>
                                        <label class="form-check-label" for="col_{{ $column }}">
                                            {{ ucwords(str_replace('_', ' ', $column)) }}
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="section-group">
                                <h5 class="section-title">Performance</h5>
                                <div class="column-list">
                                    @foreach(['results', 'cost_per_result', 'views', 'viewers', 'amount_spent'] as $column)
                                    <div class="form-check">
                                        <input class="form-check-input column-checkbox" type="checkbox"
                                            value="{{ $column }}" id="col_{{ $column }}" checked>
                                        <label class="form-check-label" for="col_{{ $column }}">
                                            {{ ucwords(str_replace('_', ' ', $column)) }}
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="section-group">
                                <h5 class="section-title">Engagement</h5>
                                <div class="column-list">
                                    @foreach(['post_engagements', 'post_reactions', 'post_comments', 'post_shares', 'post_saves', 'link_clicks', 'follows', 'ctr'] as $column)
                                    <div class="form-check">
                                        <input class="form-check-input column-checkbox" type="checkbox"
                                            value="{{ $column }}" id="col_{{ $column }}">
                                        <label class="form-check-label" for="col_{{ $column }}">
                                            {{ ucwords(str_replace('_', ' ', $column)) }}
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <!-- Video Section -->
                            <div class="section-group">
                                <h5 class="section-title">Video</h5>
                                <div class="column-list">
                                    @foreach(['3_second_video_plays', 'video_avg_play_time', 'thruplays'] as $column)
                                    <div class="form-check">
                                        <input class="form-check-input column-checkbox" type="checkbox"
                                            value="{{ $column }}" id="col_{{ $column }}">
                                        <label class="form-check-label" for="col_{{ $column }}">
                                            {{ ucwords(str_replace('_', ' ', $column)) }}
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="reorder-section">
                                <h5>Reorder columns</h5>
                                <div id="columnOrderList" class="sortable-list">
                                    @foreach(['title', 'status', 'results', 'cost_per_result', 'amount_spent', 'views', 'viewers', 'budget', 'start_date', 'end_date'] as $column)
                                    <div class="sortable-item" data-column="{{ $column }}">
                                        <div class="d-flex align-items-center justify-content-between p-1 border rounded mb-1 bg-light">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-grip-vertical text-muted me-2"></i>
                                                <span>{{ ucwords(str_replace('_', ' ', $column)) }}</span>
                                            </div>
                                            <button class="btn btn-sm btn-outline-danger remove-column">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="applyColumns">Apply</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    const FACEBOOK_BASE_URL = "{{ url('/facebook') }}";

    $(document).ready(function() {
        const adSelect = $('#ad_select');
        const container = $('#ads-summary-container');
        let sortable = null;
        let currentPage = 1;
        let currentAfter = null;

        function initializeDateRange() {
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
                currentPage = 1; 
                currentAfter = null;
                if (adSelect.val()) {
                    loadAdsSummary(adSelect.val(), start, end);
                }
            });

            $('.daterange').on('cancel.daterangepicker', function() {
                $(this).val('');
                const defaultStart = moment().subtract(28, 'days').format('YYYY-MM-DD');
                const defaultEnd = moment().subtract(1, 'days').format('YYYY-MM-DD');
                currentPage = 1;
                currentAfter = null;

                if (adSelect.val()) {
                    loadAdsSummary(adSelect.val(), defaultStart, defaultEnd);
                }
            });

            $('.daterange').val(`${defaultStart.format('YYYY-MM-DD')} - ${defaultEnd.format('YYYY-MM-DD')}`);

            if (adSelect.val()) {
                loadAdsSummary(adSelect.val(), defaultStart.format('YYYY-MM-DD'), defaultEnd.format('YYYY-MM-DD'));
            }
        }

        function loadAdsSummary(adAccountId, startDate = null, endDate = null, page = 1, after = null, campaignFilter = null) {
            container.html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><div class="mt-2">Loading ads data...</div></div>');
            const params = {
                page: page,
                limit: 50
            };
            if (after) {
                params.after = after;
            }
            if (startDate && endDate) {
                params.date_range = `${startDate} - ${endDate}`;
            }
            if (campaignFilter && campaignFilter.length > 0) {
                params['campaign_filter[]'] = campaignFilter;
            }
            /* console.log('Sending params:', params); */
            $.ajax({
                url: `${FACEBOOK_BASE_URL}/ads-summary/${adAccountId}`,
                type: 'GET',
                data: params,
                traditional: true,
                success: function(response) {
                    if (response.success) {
                        container.html(response.html);
                        currentPage = page;
                        currentAfter = after;
                        initializeSelect2Modal();
                        bindPaginationEvents();
                        bindCampaignFilterEvents();
                        if (response.date_range) {
                            console.log('Data loaded for range:', response.date_range);
                        }
                    } else {
                        container.html(`<div class="alert alert-danger py-4">${response.message || 'Failed to load ads'}</div>`);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading ads:', error);
                    let errorMessage = 'Failed to load ads. Please try again.';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    container.html(`<div class="alert alert-danger py-4">${errorMessage}</div>`);
                }
            });
        }

        function bindPaginationEvents() {
            $('.pagination-btn').off('click').on('click', function() {
                const page = $(this).data('page');
                const after = $(this).data('after');
                const currentRange = $('.daterange').val();
                const adAccountId = adSelect.val();

                if (adAccountId && currentRange) {
                    const dates = currentRange.split(' - ');
                    const campaignFilterSelect = $('#select_ad_campaigns');
                    let campaignFilter = [];                    
                    if (campaignFilterSelect.length > 0) {
                        campaignFilter = campaignFilterSelect.val() || [];
                    }                    
                    loadAdsSummary(adAccountId, dates[0], dates[1], page, after, campaignFilter);
                }
            });
        }

        function bindCampaignFilterEvents() {
            $('#select_ad_campaigns').off('change').on('change', function() {
                const campaignFilter = $(this).val();
                console.log('Selected campaign filter:', campaignFilter);                 
                const currentRange = $('.daterange').val();
                const adAccountId = adSelect.val();
                if (adAccountId && currentRange) {
                    const dates = currentRange.split(' - ');
                    currentPage = 1;
                    currentAfter = null;
                    loadAdsSummary(adAccountId, dates[0], dates[1], 1, null, campaignFilter);
                }
            });

            $('#resetFilterBtn').off('click').on('click', function() {
                $('#select_ad_campaigns').val(null).trigger('change');
                $('#select_ad_campaigns').select2();
                const currentRange = $('.daterange').val();
                const adAccountId = adSelect.val();
                if (adAccountId && currentRange) {
                    const dates = currentRange.split(' - ');
                    currentPage = 1;
                    currentAfter = null;
                    loadAdsSummary(adAccountId, dates[0], dates[1], 1, null, []);
                }
            });
        }

        initializeDateRange();
        adSelect.on('change', function() {
            const id = $(this).val();
            currentPage = 1;
            currentAfter = null;
            if (id) {
                const currentRange = $('.daterange').val();
                if (currentRange) {
                    const dates = currentRange.split(' - ');
                    loadAdsSummary(id, dates[0], dates[1]);
                } else {
                    const defaultStart = moment().subtract(28, 'days').format('YYYY-MM-DD');
                    const defaultEnd = moment().subtract(1, 'days').format('YYYY-MM-DD');
                    loadAdsSummary(id, defaultStart, defaultEnd);
                }
            }
        });
    });

    function initializeSelect2Modal() {
        $('.js-example-basic-single, .js-example-basic-multiple').each(function() {
            if ($(this).hasClass("select2-hidden-accessible")) {
                $(this).select2('destroy');
            }
        });

        $('.js-example-basic-single').select2({
            placeholder: "Select Campaigns Name",
            allowClear: true,
            minimumResultsForSearch: 0
        });

        $('.js-example-basic-multiple').select2({
            placeholder: "Select Campaigns Name",
            allowClear: true,
            minimumResultsForSearch: 0
        });
    }
</script>
@endpush