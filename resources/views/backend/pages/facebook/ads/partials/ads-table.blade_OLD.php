@if(!empty($ads))
<div class="ad-filter-section">
    <div class="d-flex flex-wrap align-items-center p-2 gap-1">
        <div class="d-flex align-items-center border-end pe-1">
            <h4 class="mb-0 me-2 text-dark-grey">Campaigns</h4>
            <select class="js-example-basic-multiple" name="select_ad_campaigns[]" id="select_ad_campaigns" multiple="multiple" required="" style="width: 400px;">
                <option value="" disabled>Select Campaigns</option>                        
                @foreach($campaigns as $campaign)
                    <option value="{{ $campaign['id'] }}">{{ $campaign['name'] }}</option>
                @endforeach
            </select>
        </div> 
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
        <thead class="table-light">
            <tr>
                @foreach($columns as $column)
                @php
                $columnNames = [
                'title' => 'Title',
                'status' => 'Status',
                'results' => 'Results',
                'cost_per_result' => 'Cost / Result',
                'amount_spent' => 'Amount Spent',
                'views' => 'Views',
                'viewers' => 'Viewers',
                'budget' => 'Budget',
                'start_date' => 'Start Date',
                'end_date' => 'End Date',
                'post_engagements' => 'Post Engagements',
                'post_reactions' => 'Post Reactions',
                'post_comments' => 'Post Comments',
                'post_shares' => 'Post Shares',
                'post_saves' => 'Post Saves',
                'link_clicks' => 'Link Clicks',
                'follows' => 'Follows',
                'ctr' => 'CTR',
                '3_second_video_plays' => '3-Second Video Plays',
                'video_avg_play_time' => 'Video Avg Play Time',
                'thruplays' => 'ThruPlays'
                ];
                @endphp
                <th>{{ $columnNames[$column] ?? ucwords(str_replace('_', ' ', $column)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($ads as $ad)
            <tr>
                @foreach($columns as $column)
                <td>
                    @if($column === 'status')
                    @if($ad[$column] === 'Active')
                    <span class="badge bg-success">{{ $ad[$column] }}</span>
                    @else
                    <span class="badge bg-secondary">{{ $ad[$column] }}</span>
                    @endif
                    @elseif(in_array($column, ['amount_spent', 'cost_per_result']) && $ad[$column] !== '-')
                    {{ $ad[$column] }}
                    @elseif(in_array($column, ['views', 'viewers', 'results', 'link_clicks']))
                    {{ number_format($ad[$column]) }}
                    @else
                    {{ $ad[$column] ?? '-' }}
                    @endif
                </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="text-muted py-3">No ads found for this account.</div>
@endif