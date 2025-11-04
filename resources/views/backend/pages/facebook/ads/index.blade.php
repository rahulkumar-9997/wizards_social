@extends('backend.pages.layouts.master')
@section('title', 'Facebook Ads')

@push('styles')
@endpush

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

                        <div class="ads-select-option" style="width: 300px;">
                            @if(!empty($adAccount['data']) && count($adAccount['data']) > 0)
                            <select class="form-control" id="ad_select"
                                name="ad_account_id"
                                data-choices
                                data-placeholder="Select Facebook Ad Account"
                                required>
                                <option value="">Choose Ad Account</option>
                                @foreach($adAccount['data'] as $adsRow)
                                <option value="{{ $adsRow['id'] }}">
                                    {{ $adsRow['name'] }} ({{ $adsRow['id'] }})
                                </option>
                                @endforeach
                            </select>
                            @else
                            <div class="text-muted small mt-2">No Ad Accounts Found</div>
                            @endif
                        </div>
                    </div>

                    <div class="card-body">
                        {{-- You can later load ads or campaigns dynamically via AJAX here --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const FACEBOOK_BASE_URL = "{{ url('/facebook') }}";
    window.FACEBOOK_BASE_URL = FACEBOOK_BASE_URL;
</script>
@endpush