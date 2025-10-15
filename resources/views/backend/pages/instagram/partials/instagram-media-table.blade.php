<table class="table align-middle mb-0 table-hover table-centered">
    <thead>
        <tr>
            <th>#</th>
            <th>Media</th>
            <th>Caption</th>
            <th>Likes</th>
            <th>Comments</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($media as $index => $post)
        <tr>
            <td>{{ ($media->currentPage() - 1) * $media->perPage() + $index + 1 }}</td>
            <td style="width:150px;">
                @if(isset($post['media_type']))
                    @if($post['media_type'] === 'VIDEO')
                    <video width="100" height="100" controls>
                        <source src="{{ $post['media_url'] }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    @else
                    <img src="{{ $post['media_url'] }}" alt="Media" class="img-fluid" style="max-width:100px; max-height:100px;">
                    @endif
                @endif
            </td>
            <td>{{ \Illuminate\Support\Str::limit($post['caption'] ?? '-', 40) }}</td>
            <td>❤️ {{ $post['like_count'] ?? 0 }}</td>
            <td>💬 {{ $post['comments_count'] ?? 0 }}</td>
            <td>
                <div class="d-flex gap-1">
                    @if(isset($post['permalink']))
                    <a href="{{ $post['permalink'] }}" target="_blank" class="btn btn-sm btn-outline-primary">
                        View on Instagram
                    </a>
                    <a href="{{ $post['permalink'] }}" target="_blank" class="btn btn-sm btn-outline-pink">
                        View Insights
                    </a>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center">No posts found.</td>
        </tr>
        @endforelse
    </tbody>
</table>

@if($media->hasPages())
<div class="my-pagination" id="multiple_update" style="margin-top: 20px;">
    {{ $media->appends(request()->query())->links('vendor.pagination.bootstrap-4') }}
</div>
@endif