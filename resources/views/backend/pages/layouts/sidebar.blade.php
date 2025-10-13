<!-- ========== App Menu Start ========== -->
<div class="main-nav">
   <div class="logo-box">
      <a href="{{route('dashboard')}}" class="logo-dark">
      <img src="{{asset('backend/assets/fav-icon.png')}}" class="logo-sm" alt="logo sm">
      <img src="{{asset('backend/assets/fav-icon.png')}}" class="logo-lg" alt="logo dark">
      </a>
      <a href="{{route('dashboard')}}" class="logo-light" style="text-align: center;">
      <img src="{{asset('backend/assets/fav-icon.png')}}" class="logo-sm" alt="logo sm">
      <img src="{{asset('backend/assets/logo.png')}}" style="width: 177px; height: 45px;" class="logo-lg" alt="logo light">
      </a>
   </div>
   <button type="button" class="button-sm-hover" aria-label="Show Full Sidebar">
      <iconify-icon icon="solar:double-alt-arrow-right-bold-duotone" class="button-sm-hover-icon"></iconify-icon>
   </button>
   <div class="scrollbar" data-simplebar>
      <ul class="navbar-nav" id="navbar-nav">
         <li class="nav-item active">
            <a class="nav-link active" href="{{ route('dashboard') }}">
               <span class="nav-icon">
                  <iconify-icon icon="solar:widget-5-bold-duotone"></iconify-icon>
               </span>
               <span class="nav-text">Dashboard </span>
            </a>
         </li>
         <li class="nav-item">
            <a class="nav-link menu-arrow" href="#sidebarProducts_user" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarProducts_user">
               <span class="nav-icon">
                  <iconify-icon icon="solar:users-group-two-rounded-bold-duotone"></iconify-icon>
               </span>
               <span class="nav-text"> Manage User </span>
            </a>
            <div class="collapse" id="sidebarProducts_user">
               <ul class="nav sub-navbar-nav">
                 
                     <li class="sub-nav-item">
                        <a class="sub-nav-link" href="{{ route('users.index') }}">User</a>
                     </li>
                     <li class="sub-nav-item">
                        <a class="sub-nav-link" href="{{ route('roles.index') }}">Role</a>
                     </li>
                     <li class="sub-nav-item">
                        <a class="sub-nav-link" href="{{ route('permissions.index') }}">Permissions</a>
                     </li>
                 
               </ul>
            </div>
         </li>
         <li class="nav-item">
            <a class="nav-link" href="{{ route('facebook.index') }}">
               <span class="nav-icon">
                     <iconify-icon icon="logos:facebook"></iconify-icon>
               </span>
               <span class="nav-text">Facebook</span>
            </a>
         </li>
         <li class="nav-item">
            <a class="nav-link" href="{{ route('instagram.index') }}">
               <span class="nav-icon">
                     <iconify-icon icon="skill-icons:instagram"></iconify-icon>
               </span>
               <span class="nav-text">Instagram</span>
            </a>
         </li>
         <li class="nav-item">
            <a class="nav-link" href="{{ route('youtube.index') }}">
               <span class="nav-icon">
                     <iconify-icon icon="logos:youtube-icon"></iconify-icon>
               </span>
               <span class="nav-text">YouTube</span>
            </a>
         </li>
         
         
         
         
         
         
      </ul>
   </div>
</div>
<!-- ========== App Menu End ========== -->