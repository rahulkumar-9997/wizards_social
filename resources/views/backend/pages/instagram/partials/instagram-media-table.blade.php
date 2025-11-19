@if(!empty($startDate) && !empty($endDate))
<div class="mb-2">
    <strong>Showing posts from {{ $startDate }} to {{ $endDate }}</strong>
</div>
@endif

@if(isset($currentFilters['media_type']) && !empty($currentFilters['media_type']) || isset($currentFilters['search']) && !empty($currentFilters['search']))
<div class="mb-2">
    <small class="text-muted">
        Filters: 
        @if(isset($currentFilters['media_type']) && !empty($currentFilters['media_type']))
        Media Type: {{ $currentFilters['media_type'] }}
        @endif
        @if(isset($currentFilters['search']) && !empty($currentFilters['search']))
        Search: "{{ $currentFilters['search'] }}"
        @endif
    </small>
</div>
@endif

<table class="table align-middle mb-0 table-hover table-centered" id="instagram-posts-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Media</th>
            <th data-sort="timestamp" data-order="{{ isset($currentSort) && $currentSort['field'] == 'timestamp' ? $currentSort['order'] : 'none' }}" title="Click to sort">
                Date Published 
                <span class="sort-arrow">
                    @if(isset($currentSort) && $currentSort['field'] == 'timestamp')
                        @if($currentSort['order'] == 'asc')
                        <i class="bx bx-sort-up"></i>
                        @else
                        <i class="bx bx-sort-down"></i>
                        @endif
                    @else
                    <i class="bx bx-sort-alt-2"></i>
                    @endif
                </span>
            </th>
            
            <th>Caption</th>
            <th data-sort="media_type" data-order="{{ isset($currentSort) && $currentSort['field'] == 'media_type' ? $currentSort['order'] : 'none' }}" title="Click to sort">
                Media Type 
                <span class="sort-arrow">
                    @if(isset($currentSort) && $currentSort['field'] == 'media_type')
                        @if($currentSort['order'] == 'asc')
                        <i class="bx bx-sort-up"></i>
                        @else
                        <i class="bx bx-sort-down"></i>
                        @endif
                    @else
                    <i class="bx bx-sort-alt-2"></i>
                    @endif
                </span>
            </th>
            <th data-sort="like_count" data-order="{{ isset($currentSort) && $currentSort['field'] == 'like_count' ? $currentSort['order'] : 'none' }}" title="Click to sort">
                Likes 
                <span class="sort-arrow">
                    @if(isset($currentSort) && $currentSort['field'] == 'like_count')
                        @if($currentSort['order'] == 'asc')
                        <i class="bx bx-sort-up"></i>
                        @else
                        <i class="bx bx-sort-down"></i>
                        @endif
                    @else
                    <i class="bx bx-sort-alt-2"></i>
                    @endif
                </span>
            </th>
            <th data-sort="comments_count" data-order="{{ isset($currentSort) && $currentSort['field'] == 'comments_count' ? $currentSort['order'] : 'none' }}" title="Click to sort">
                Comments 
                <span class="sort-arrow">
                    @if(isset($currentSort) && $currentSort['field'] == 'comments_count')
                        @if($currentSort['order'] == 'asc')
                        <i class="bx bx-sort-up"></i>
                        @else
                        <i class="bx bx-sort-down"></i>
                        @endif
                    @else
                    <i class="bx bx-sort-alt-2"></i>
                    @endif
                </span>
            </th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($media as $index => $post)
        <tr class="post-row">
            <td>{{ (($pagination['current_page'] ?? 1) - 1) * ($pagination['per_page'] ?? 12) + $index + 1 }}</td>
            <td>
                @if(isset($post['media_type']))
                @if($post['media_type'] === 'VIDEO')
                <video width="70" height="70" muted autoplay loop playsinline style="object-fit:cover; border-radius:6px;">
                    <source src="{{ $post['media_url'] }}" type="video/mp4">
                </video>
                @else
                <img src="{{ $post['media_url'] }}" alt="Media" class="img-fluid img-thumbnail" style="max-width:70px; max-height:88px;">
                @endif
                @endif
            </td>
            <td>{{ isset($post['timestamp']) ? \Carbon\Carbon::parse($post['timestamp'])->format('d-m-Y h:i A') : '-' }}</td>
            
            <td>{{ \Illuminate\Support\Str::limit($post['caption'] ?? '-', 40) }}</td>
            <td>
                <span class="badge 
                    @if($post['media_type'] === 'IMAGE') bg-success
                    @elseif($post['media_type'] === 'VIDEO') bg-primary
                    @elseif($post['media_type'] === 'CAROUSEL_ALBUM') bg-warning
                    @elseif($post['media_type'] === 'REELS') bg-danger
                    @else bg-secondary @endif">
                    {{ $post['media_type'] ?? '-' }}
                </span>
            </td>
            <td>❤️ {{ $post['like_count'] ?? 0 }}</td>
            <td>💬 {{ $post['comments_count'] ?? 0 }}</td>
            <td>
                <div class="d-flex gap-1">
                    @if(isset($post['permalink']))
                    <a href="{{ $post['permalink'] }}" target="_blank" class="btn btn-soft-primary btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post on instagram">
                        <i class="ti ti-brand-instagram"></i>
                        View Instagram
                    </a>
                    @endif
                    <a href="{{ route('instagram.post.insights.page', ['id' => $instagram['id'], 'postId' => $post['id']]) }}"
                        class="btn btn-soft-warning btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post data">
                        <i class="bx bx-bar-chart"></i> View Insights
                    </a>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" class="text-center">No posts found.</td>
        </tr>
        @endforelse
    </tbody>
</table>

@if(isset($pagination) && ($pagination['has_previous'] || $pagination['has_next']))
<div class="d-flex justify-content-end gap-2 mt-3 pagination">
    @if($pagination['has_previous'])
    <a href="{{ $pagination['previous_page_url'] }}" class="btn btn-outline-primary btn-sm page-link">← Previous</a>
    @endif

    @if($pagination['has_next'])
    <a href="{{ $pagination['next_page_url'] }}" class="btn btn-outline-primary btn-sm page-link">Next →</a>
    @endif
</div>
@endif