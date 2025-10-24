<table class="table align-middle mb-0 table-hover table-centered">
    <thead>
        <tr>
            <th>#</th>
            <th>Media</th>
            <th>Post Date</th>
            <th>Caption</th>
            <th>Media Type</th>
            <th>Likes</th>
            <th>Comments</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($media as $index => $post)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td style="width:150px;">
                @if(isset($post['media_type']))
                    @if($post['media_type'] === 'VIDEO')
                        <video width="100" height="100" controls>
                            <source src="{{ $post['media_url'] }}" type="video/mp4">
                        </video>
                    @else
                        <img src="{{ $post['media_url'] }}" alt="Media" class="img-fluid" style="max-width:100px; max-height:100px;">
                    @endif
                @endif
            </td>
            <td>{{ isset($post['timestamp']) ? \Carbon\Carbon::parse($post['timestamp'])->format('d-m-Y h:i A') : '-' }}</td>
            <td>{{ \Illuminate\Support\Str::limit($post['caption'] ?? '-', 40) }}</td>
            <td>{{ $post['media_type'] ?? '-' }}</td>
            <td>❤️ {{ $post['like_count'] ?? 0 }}</td>
            <td>💬 {{ $post['comments_count'] ?? 0 }}</td>
            <td>
                <div class="d-flex gap-1">
                    @if(isset($post['permalink']))
                        <a href="{{ $post['permalink'] }}" target="_blank" class="btn btn-soft-primary btn-sm" data-bs-toggle="tooltip"data-bs-original-title="View this post on instagram">
                            <i class="ti ti-brand-instagram"></i>
                            View Instagram
                        </a>
                    @endif
                    <a href="{{ route('instagram.post.insights.page', ['id' => $instagram['id'], 'postId' => $post['id']]) }}" 
                    class="btn btn-soft-warning btn-sm" data-bs-toggle="tooltip"data-bs-original-title="View this post data">
                        <i class="bx bx-bar-chart"></i> View Insights
                    </a>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center">No posts found.</td>
        </tr>
        @endforelse
    </tbody>
</table>
@if(isset($paging))
<div class="d-flex justify-content-end gap-2 mt-3 pagination">
    @if(isset($paging['previous']))
        <a href="{{ request()->fullUrlWithQuery(['before' => $paging['cursors']['before'] ?? null, 'after' => null]) }}"
           class="btn btn-outline-primary btn-sm page-link">← Previous</a>
    @endif

    @if(isset($paging['next']))
        <a href="{{ request()->fullUrlWithQuery(['after' => $paging['cursors']['after'] ?? null, 'before' => null]) }}"
           class="btn btn-outline-primary btn-sm page-link">Next →</a>
    @endif
</div>
@endif
