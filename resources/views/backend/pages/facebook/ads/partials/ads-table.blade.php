
<div class="ad-filter-section">
    <div class="d-flex flex-wrap align-items-center p-2 gap-1">
        <div class="d-flex align-items-center border-end pe-1">
            <h4 class="mb-0 me-2 text-dark-grey">Campaigns</h4>
            <select class="js-example-basic-multiple" name="select_ad_campaigns[]" id="select_ad_campaigns" multiple="multiple" required="" style="width: 400px;">
                <option value="" disabled>Select Campaigns</option>
                @foreach($campaigns as $campaign)
                <option value="{{ $campaign['id'] }}"
                    @if(isset($campaignFilter) && in_array($campaign['id'], $campaignFilter)) selected @endif>
                    {{ $campaign['name'] }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="ms-2">
            <button type="button" id="resetFilterBtn" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-redo"></i> Reset
            </button>
        </div>
    </div>
</div>
@if(!empty($processedAds))
<div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
        <tr>
            <th>Sr. No.</th>
            <th>Title</th>
            <th>Status</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Amount Spent</th>
            <th>Reach</th>
            <th>Ad Creative Url</th>
        </tr>
        @foreach($processedAds as $ad)
        <tr>
            <td>{{ ($pagination['current_page'] - 1) * $pagination['per_page'] + $loop->iteration }}</td>
            <td>
                <div class="d-flex align-items-center pe-1 gap-2">
                    <div class="ad-img">
                        @if($ad['ad_thumbnail'])
                        <img src="{{ $ad['ad_thumbnail'] }}" class="img-fluid img-thumbnail" style="max-width:70px; max-height:88px;">
                        @else
                        No Thumbnail
                        @endif
                    </div>
                    <div class="ad-title">
                        {{ $ad['title'] }}
                        <br>
                        <small class="text-muted">({{ $ad['campaign_name'] }})</small>
                    </div>
                    <br>
                    ({{ $ad['campaign_id'] }})
                </div>
            </td>
            <td>
                {{ $ad['status'] }}
            </td>
            <td>
                @if(!empty($ad['start_date']) && strtotime($ad['start_date']))
                {{ \Carbon\Carbon::parse($ad['start_date'])->format('M d h:i:s A') }}
                @else
                {{ $ad['start_date'] }}
                @endif
            </td>

            <td>
                @if(!empty($ad['end_date']) && strtotime($ad['end_date']))
                {{ \Carbon\Carbon::parse($ad['end_date'])->format('M d h:i:s A') }}
                @else
                {{ $ad['end_date'] }}
                @endif
            </td>

            <td>{{ $ad['amount_spent'] }}</td>
            <td>{{ $ad['viewers'] }}</td>
            <td>
                @if($ad['ad_creative_url'])
                <a href="{{ $ad['ad_creative_url'] }}" target="_blank">
                    {{ $ad['ad_creative_url'] }}
                </a>
                @else
                N/A
                @endif
            </td>

        </tr>
        @endforeach
    </table>
</div>

@if(isset($pagination) && ($pagination['has_previous'] || $pagination['has_next']))
<div class="d-flex justify-content-end gap-2 mt-3 pagination">
    @if($pagination['has_previous'])
    <button type="button" class="btn btn-outline-primary btn-sm pagination-btn"
        data-page="{{ $pagination['current_page'] - 1 }}"
        data-after="{{ $pagination['prev_cursor'] }}">
        ← Previous
    </button>
    @endif

    @if($pagination['has_next'])
    <button type="button" class="btn btn-outline-primary btn-sm pagination-btn"
        data-page="{{ $pagination['current_page'] + 1 }}"
        data-after="{{ $pagination['next_cursor'] }}">
        Next →
    </button>
    @endif
</div>
@endif

@else
<div class="text-muted py-3">No ads found for this account.</div>
@endif