<header class="topbar">
   <div class="container-fluid">
      <div class="navbar-header">
         <div class="d-flex align-items-center">
            <div class="topbar-item">
               <button type="button" class="button-toggle-menu me-2">
                  <iconify-icon icon="solar:hamburger-menu-broken" class="fs-24 align-middle"></iconify-icon>
               </button>
            </div>
            <div class="topbar-item">
               <h4 class="fw-bold topbar-button pe-none text-uppercase mb-0">Welcome!</h4>
            </div>
         </div>
         <div class="d-flex align-items-center gap-1">
            <a class="btn btn-outline-info" href="{{route('show.tables')}}">Database</a>
            <a class="btn btn-outline-purple" href="{{route('clear-cache')}}">Clear cache</a>
            <div class="dropdown topbar-item">
               <button type="button" class="topbar-button position-relative" id="page-header-notifications-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <iconify-icon icon="solar:bell-bing-bold-duotone" class="fs-24 align-middle"></iconify-icon>
                  <span class="position-absolute topbar-badge fs-10 translate-middle badge bg-danger rounded-pill">0<span class="visually-hidden">unread messages</span></span>
               </button>
               
            </div>
            <!-- User -->
            <div class="dropdown topbar-item">
               <a type="button" class="topbar-button" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
               <span class="d-flex align-items-center">
                  @if(Auth::user()->profile_img)
                     <img src="{{ asset('profile-images/'.Auth::user()->profile_img) }}" alt="" class="rounded-circle" width="32">
                  @else
                     <img class="rounded-circle" width="32" src="{{asset('backend/assets/images/users/avatar-1.jpg')}}" alt="avatar-3">
                  @endif
                  
               </span>
               </a>
               <div class="dropdown-menu dropdown-menu-end">
                  <h6 class="dropdown-header">
                     Welcome {{auth()->user()->name ?? ''}}!
                  </h6>
                  <a class="dropdown-item" href="{{route('users.profile')}}">
                     <i class="bx bx-user-circle text-muted fs-18 align-middle me-1"></i>
                     <span class="align-middle">Profile</span>
                  </a>
                  <div class="dropdown-divider my-1"></div>
                  <a class="dropdown-item text-danger" href="{{route('logout')}}">
                  <i class="bx bx-log-out fs-18 align-middle me-1"></i><span class="align-middle">Logout</span>
                  </a>
               </div>
            </div>
            <!-- App Search-->
            <form class="app-search d-none d-md-block ms-2">
               <div class="position-relative">
                  <input type="search" class="form-control" placeholder="Search..." autocomplete="off" value="">
                  <iconify-icon icon="solar:magnifer-linear" class="search-widget-icon"></iconify-icon>
               </div>
            </form>
         </div>
      </div>
   </div>
</header>