<div class="mandate-section">
    <div class="mandate-header-top">
        <h3 class="mandate-title">
            REACH
            @php
                $tooltipText = trim($reach['api_description'] ?? '');
            @endphp
            <i class="bx bx-question-mark text-primary"
            style="cursor:pointer;font-size:18px;"
            data-bs-toggle="tooltip"
            data-bs-placement="top"
            data-bs-custom-class="success-tooltip"
            data-bs-title="{{ $tooltipText !== '' ? $tooltipText : 'No description available' }}">
            </i>
        </h3>
        <p style="text-align: justify;">
            It shows how many different people discovered
            your profile, not how many times it was viewed.
            Profile reach helps you understand your brand
            visibility and audience interest. Higher profile
            reach often means your content is attracting
            new users to your page.
        </p>
    </div>
    <div class="mandate-item">
        <div class="mandate-item-header" style="box-shadow:  0 15px 13px -7px rgba(0, 0, 0, 0.2); padding: 10px; margin-bottom: 10px;">
            <div class="d-flex justify-content-between align-items-center gap-1">
                <div class="mandate-item-title">
                    <h5 style="margin-bottom: 0px;">Previous Month</h5>
                    <div class="mandate-item-text">
                        <h3 class="mb-0">{{ compact_number($reach['previous'] ?? 0) }}</h3>
                    </div>
                </div>

                <div class="mandate-item-title">
                    <h5 style="margin-bottom: 0px;">Current Month</h5>
                    <div class="mandate-item-text">
                        <h2 class="mb-0">{{ compact_number($reach['current'] ?? 0) }}</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="mandate-item-body">
            <div class="d-flex justify-content-between align-items-center gap-1">
                <div class="mandate-item-title d-flex flex-wrap">
                    <div class="col-custom-2">
                        <h5 style="margin-bottom: 0px;">Paid</h5>
                        <h3>
                            {{ compact_number($reach['paid'] ?? 0) }}
                            
                        </h3>
                    </div>
                    <div class="col-custom-2">
                        <h5 style="margin-bottom: 0px;">Organic</h5>
                        <h3>
                            {{ compact_number($reach['organic'] ?? 0) }}
                        </h3>
                    </div>
                    <div class="col-custom-2">
                        <h5 style="margin-bottom: 0px;">Follower</h5>
                        <h3>{{ compact_number($reach['followers'] ?? 0) }}</h3>
                    </div>
                    <div class="col-custom-2">
                        <h5 style="margin-bottom: 0px;">Non - Follower</h5>
                        <h3>{{ compact_number($reach['non_followers'] ?? 0) }}</h3>
                    </div>
                </div>
                <div class="mandate-item-arrow">
                    @php
                        $percentChange = $reach['percent_change'] ?? 0;
                        $isPositive = $percentChange >= 0;
                        $color = $isPositive ? '#28a745' : '#e70000ff';
                        $arrow = $isPositive ? 'green-arrow-up.png' : 'red-arrow-down.png';
                    @endphp
                    <h4 style="margin-bottom: 5px; color: {{ $color }}; font-size: 24px;">
                        {{ abs($percentChange) }}%
                    </h4>
                    <div class="mandate-arrow-icon">
                        <img src="{{ asset('backend/assets/' . $arrow) }}" 
                             alt="{{ $isPositive ? 'Up Arrow' : 'Down Arrow' }}" 
                             width="24" height="24">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>