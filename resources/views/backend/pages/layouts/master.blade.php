<!DOCTYPE html>
<html lang="en">
    <head>
        @include('backend.pages.layouts.head')
        @stack('styles')
    </head>
    <body class="theme-3">
        <div class="wrapper">
            @include('backend.pages.layouts.header')
            @include('backend.pages.layouts.sidebar')
            <div class="page-content">
                @yield('main-content')
                @include('backend.pages.layouts.footer')
            </div>
        </div>
        @include('backend.pages.layouts.footer-js')        
        @stack('scripts')
        <script src="{{asset('backend/assets/js/common-ajax.js')}}" type="text/javascript"></script>
    </body>
</html>