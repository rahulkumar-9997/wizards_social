@extends('backend.pages.layouts.master')
@section('title', 'Facebook Ads')

@section('main-content')
<div class="container-fluid">
    <div class="row">
        @include('backend.pages.layouts.second-sidebar')

        <div class="col-xl-9">
            <div class="ads-section">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center gap-1">
                        <div class="text-white">
                            <h4 class="card-title mb-0">Ads</h4>
                        </div>

                        <div class="d-flex gap-2">
                            <!-- Customize Columns Button -->
                            <button class="btn btn-outline-light" id="customizeColumnsBtn">
                                <i class="fas fa-columns me-1"></i> Customize Columns
                            </button>

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

@push('styles')
<style>
.ads-customize-coloumn{
    min-height: 100px;
    max-height: 500px;
    overflow-y: auto;
}
.section-group {
    margin-bottom: 5px;
}
.section-title {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #333;
}

.sortable-item {
    cursor: move;
}
.sortable-item.sortable-ghost {
    opacity: 0.4;
}
.sortable-item.sortable-chosen {
    background-color: #f8f9fa;
}
.form-check {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
}
.form-check:last-child {
    border-bottom: none;
}
#customizeColumnsModal .form-check .form-check-input{
    margin-left: 0px;
}
#customizeColumnsModal .form-check-label{
    margin-left: 10px;
}
#customizeColumnsModal .form-check {
    padding: 0.2rem 0;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    const FACEBOOK_BASE_URL = "{{ url('/facebook') }}";

    $(document).ready(function() {
        const adSelect = $('#ad_select');
        const container = $('#ads-summary-container');
        let currentColumns = JSON.parse(localStorage.getItem('facebook_ads_columns') || '["title","status","results","cost_per_result","amount_spent","views","viewers","budget"]');
        let sortable = null;

        // Load ads summary via AJAX
        function loadAdsSummary(adAccountId) {
            container.html('<div class="text-muted py-4">Loading ads data...</div>');
            
            $.ajax({
                url: `${FACEBOOK_BASE_URL}/ads-summary/${adAccountId}`,
                type: 'GET',
                data: {
                    columns: currentColumns.join(',')
                },
                success: function(response) {
                    container.html(response.html);
                },
                error: function() {
                    container.html('<div class="text-danger py-4">Failed to load ads. Try again.</div>');
                }
            });
        }

        // Initialize Sortable
        function initializeSortable() {
            if (sortable) {
                sortable.destroy();
            }
            
            const columnOrderList = document.getElementById('columnOrderList');
            if (columnOrderList) {
                sortable = new Sortable(columnOrderList, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    onEnd: function(evt) {
                        // Update currentColumns based on new order
                        updateCurrentColumnsFromSortable();
                    }
                });
            }
        }

        // Update currentColumns based on sortable order
        function updateCurrentColumnsFromSortable() {
            const newOrder = [];
            $('#columnOrderList .sortable-item').each(function() {
                newOrder.push($(this).data('column'));
            });
            currentColumns = newOrder;
        }

        // Load first account by default
        if (adSelect.val()) {
            loadAdsSummary(adSelect.val());
        }

        // Change listener for ad account
        adSelect.on('change', function() {
            const id = $(this).val();
            if (id) loadAdsSummary(id);
        });

        // Customize Columns Modal
        $('#customizeColumnsBtn').on('click', function() {
            initializeColumnModal();
            $('#customizeColumnsModal').modal('show');
        });

        // Initialize modal with current settings
        function initializeColumnModal() {
            // Show all form checks first
            $('.form-check').show();
            
            // Check checkboxes based on current columns
            $('.column-checkbox').each(function() {
                const column = $(this).val();
                $(this).prop('checked', currentColumns.includes(column));
            });

            // Update order list based on currentColumns
            updateOrderList();
            
            // Reinitialize sortable after a small delay to ensure DOM is updated
            setTimeout(() => {
                initializeSortable();
            }, 100);
        }

        // Update order list based on currentColumns (preserving order)
        function updateOrderList() {
            $('#columnOrderList').empty();
            
            // Only show columns that are in currentColumns AND checked
            currentColumns.forEach(column => {
                if ($(`#col_${column}`).is(':checked')) {
                    $('#columnOrderList').append(`
                        <div class="sortable-item" data-column="${column}">
                            <div class="d-flex align-items-center justify-content-between p-1 border rounded mb-1 bg-light">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-grip-vertical text-muted me-2"></i>
                                    <span>${ucwords(column.replace(/_/g, ' '))}</span>
                                </div>
                                <button class="btn btn-sm btn-outline-danger remove-column" type="button">
                                    <i class="ti ti-trash" data-bs-toggle="tooltip" data-bs-original-title="Delete"></i>
                                </button>
                            </div>
                        </div>
                    `);
                }
            });
        }

        // Remove column from order list
        $(document).on('click', '.remove-column', function(e) {
            e.preventDefault();
            const column = $(this).closest('.sortable-item').data('column');
            $(`#col_${column}`).prop('checked', false);
            
            // Remove from currentColumns
            currentColumns = currentColumns.filter(col => col !== column);
            
            updateOrderList();
            initializeSortable();
        });

        // Search columns
        $('#columnSearch').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            
            $('.form-check').each(function() {
                const label = $(this).find('.form-check-label').text().toLowerCase();
                
                if (label.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Column checkbox change event
        $(document).on('change', '.column-checkbox', function() {
            const column = $(this).val();
            const isChecked = $(this).is(':checked');
            
            if (isChecked) {
                // Add to currentColumns if not already present
                if (!currentColumns.includes(column)) {
                    currentColumns.push(column);
                }
            } else {
                // Remove from currentColumns
                currentColumns = currentColumns.filter(col => col !== column);
            }
            
            updateOrderList();
            initializeSortable();
        });

        // Apply column changes
        $('#applyColumns').on('click', function() {
            // Ensure we have at least one column
            if (currentColumns.length === 0) {
                alert('Please select at least one column to display.');
                return;
            }

            // Save to localStorage
            localStorage.setItem('facebook_ads_columns', JSON.stringify(currentColumns));
            
            $('#customizeColumnsModal').modal('hide');
            
            // Reload ads with new columns
            if (adSelect.val()) {
                loadAdsSummary(adSelect.val());
            }
        });

        // Modal hidden event - cleanup
        $('#customizeColumnsModal').on('hidden.bs.modal', function () {
            // Reset search
            $('#columnSearch').val('');
            $('.form-check').show();
        });

        // Modal shown event - reinitialize
        $('#customizeColumnsModal').on('shown.bs.modal', function () {
            initializeSortable();
        });

        // Helper function to capitalize words
        function ucwords(str) {
            return str.replace(/_/g, ' ')
                     .replace(/\w\S*/g, function(txt) {
                         return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
                     });
        }
        
        // Initial sortable initialization
        initializeSortable();
    });
</script>
@endpush