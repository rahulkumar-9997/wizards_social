<!-- ========== App Menu Start ========== -->
<div class="main-nav">
   <div class="logo-box">
      <a href="{{ route('dashboard') }}" class="logo-dark">
         <img src="{{ asset('backend/assets/fav-icon.png') }}" class="logo-sm" alt="logo sm" loading="lazy">
         <img src="{{ asset('backend/assets/fav-icon.png') }}" class="logo-lg" alt="logo dark" loading="lazy">
      </a>
      <a href="{{ route('dashboard') }}" class="logo-light text-center">
         <img src="{{ asset('backend/assets/fav-icon.png') }}" class="logo-sm" alt="logo sm" loading="lazy">
         <img src="{{ asset('backend/assets/logo.png') }}" style="width:177px; height:45px;" class="logo-lg" alt="logo light" loading="lazy">
      </a>
   </div>
   
   <button type="button" class="button-sm-hover" aria-label="Show Full Sidebar">
      <iconify-icon icon="solar:double-alt-arrow-right-bold-duotone" class="button-sm-hover-icon"></iconify-icon>
   </button>

   <div class="scrollbar" data-simplebar>
      <ul class="navbar-nav" id="navbar-nav">
         @php
            // Pre-defined colors for consistent styling
            $colors = [
               '#e74c3c','#3498db','#2ecc71','#f1c40f','#9b59b6','#e67e22','#1abc9c',
               '#ff6b6b','#00b894','#0984e3','#fdcb6e','#6c5ce7','#d63031','#00cec9',
               '#fab1a0','#55efc4','#ffeaa7','#81ecec','#636e72','#fd79a8',
            ];
            $currentRoute = request()->route()->getName();
            $menus = $menus ?? collect();
         @endphp

         @if($menus->count() > 0)
            @foreach($menus as $index => $menu)
               @php 
                  $colorIndex = $index % count($colors);
                  $iconColor = $colors[$colorIndex];
                  $isActive = $currentRoute === $menu->url;
                  $hasChildren = $menu->childrenRecursive && $menu->childrenRecursive->isNotEmpty();
               @endphp
               
               <li class="nav-item {{ $isActive ? 'active' : '' }} {{ $hasChildren ? 'has-children' : '' }}">
                  @if(!$hasChildren)
                     <!-- Single Menu Item -->
                     <a class="nav-link {{ $isActive ? 'active' : '' }}" 
                        href="{{ $menu->resolved_url ?? '#' }}"
                        title="{{ $menu->name }}">
                        <span class="nav-icon">
                           @if(str_starts_with($menu->icon ?? '', 'ti'))
                              <i class="{{ $menu->icon }}" style="color:{{ $iconColor }};"></i>
                           @else
                              <iconify-icon icon="{{ $menu->icon ?? 'solar:home-2-bold-duotone' }}" style="color:{{ $iconColor }};"></iconify-icon>
                           @endif
                        </span>
                        <span class="nav-text">{{ $menu->name }}</span>
                     </a>
                  @else
                     <!-- Parent Menu with Children -->
                     <a class="nav-link menu-arrow {{ $isActive ? 'active' : '' }}" 
                        href="#sidebar_{{ $menu->id }}" 
                        data-bs-toggle="collapse" 
                        role="button" 
                        aria-expanded="false" 
                        aria-controls="sidebar_{{ $menu->id }}"
                        title="{{ $menu->name }}">
                        <span class="nav-icon">
                           @if(str_starts_with($menu->icon ?? '', 'ti'))
                              <i class="{{ $menu->icon }}" style="color:{{ $iconColor }};"></i>
                           @else
                              <iconify-icon icon="{{ $menu->icon ?? 'solar:widget-bold-duotone' }}" style="color:{{ $iconColor }};"></iconify-icon>
                           @endif
                        </span>
                        <span class="nav-text">{{ $menu->name }}</span>
                     </a>

                     <div class="collapse" id="sidebar_{{ $menu->id }}">
                        <ul class="nav sub-navbar-nav">
                           @foreach($menu->childrenRecursive as $childIndex => $child)
                              @php
                                 $childColorIndex = ($index + $childIndex) % count($colors);
                                 $childColor = $colors[$childColorIndex];
                                 $isChildActive = $currentRoute === $child->url;
                                 $hasGrandChildren = $child->childrenRecursive && $child->childrenRecursive->isNotEmpty();
                              @endphp
                              
                              <li class="sub-nav-item {{ $isChildActive ? 'active' : '' }} {{ $hasGrandChildren ? 'has-grand-children' : '' }}">
                                 <a class="sub-nav-link {{ $isChildActive ? 'active' : '' }}" 
                                    href="{{ $child->resolved_url ?? '#' }}"
                                    title="{{ $child->name }}">
                                    @if(str_starts_with($child->icon ?? '', 'ti'))
                                       <i class="{{ $child->icon }}" style="color:{{ $childColor }};"></i>
                                    @else
                                       <iconify-icon icon="{{ $child->icon ?? 'solar:circle-small-bold-duotone' }}" style="color:{{ $childColor }};"></iconify-icon>
                                    @endif
                                    {{ $child->name }}
                                 </a>
                                 
                                 @if($hasGrandChildren)
                                    <ul class="nav sub-navbar-nav ms-3">
                                       @foreach($child->childrenRecursive as $subChildIndex => $subChild)
                                          @php
                                             $subColorIndex = ($index + $childIndex + $subChildIndex) % count($colors);
                                             $subColor = $colors[$subColorIndex];
                                             $isSubChildActive = $currentRoute === $subChild->url;
                                          @endphp
                                          <li class="sub-nav-item {{ $isSubChildActive ? 'active' : '' }}">
                                             <a class="sub-nav-link {{ $isSubChildActive ? 'active' : '' }}" 
                                                href="{{ $subChild->resolved_url ?? '#' }}"
                                                title="{{ $subChild->name }}">
                                                <iconify-icon icon="{{ $subChild->icon ?? 'solar:circle-small-bold-duotone' }}" style="color:{{ $subColor }};"></iconify-icon>
                                                {{ $subChild->name }}
                                             </a>
                                          </li>
                                       @endforeach
                                    </ul>
                                 @endif
                              </li>
                           @endforeach
                        </ul>
                     </div>
                  @endif
               </li>
            @endforeach
         @else
            <!-- No menus available message -->
            <li class="nav-item">
               <div class="nav-link text-muted">
                  <span class="nav-icon">
                     <iconify-icon icon="solar:info-circle-bold-duotone"></iconify-icon>
                  </span>
                  <span class="nav-text">No menus available</span>
               </div>
            </li>
         @endif
      </ul>
   </div>
</div>
<!-- ========== App Menu End ========== -->