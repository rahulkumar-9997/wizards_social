<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
   <meta charset="utf-8" />
   <title>Login</title>
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <meta name="author" content="Wizards" />
   <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
   <link rel="shortcut icon" href="{{asset('backend/assets/fav-icon.png')}}">
   <link href="{{asset('backend/assets/css/vendor.min.css')}}" rel="stylesheet" type="text/css" />
   <link href="{{asset('backend/assets/css/icons.min.css')}}" rel="stylesheet" type="text/css" />
   <link href="{{asset('backend/assets/css/app.min.css')}}" rel="stylesheet" type="text/css" />
   <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css"> -->
   <script src="{{asset('backend/assets/js/config.js')}}"></script>
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <style>
      .otp-section {
         display: none;
      }

      .otp-input-group {
         display: flex;
         gap: 10px;
         justify-content: center;
      }

      .otp-input {
         width: 50px;
         height: 50px;
         text-align: center;
         font-size: 18px;
         font-weight: bold;
         border: 1px solid #ced4da;
         border-radius: 5px;
      }

      .otp-input:focus {
         border-color: #86b7fe;
         outline: 0;
         box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
      }

      .resend-otp {
         margin-top: 10px;
         text-align: center;
      }

      .resend-otp a {
         cursor: pointer;
      }

      .countdown {
         color: #6c757d;
      }

      .login-options {
         margin-top: 15px;
         margin-bottom: 15px;
         text-align: center;
      }

      .login-options a {
         cursor: pointer;
         color: #0d6efd;
         text-decoration: none;
      }

      .login-options a:hover {
         text-decoration: underline;
      }

      .btn-loading {
         position: relative;
         pointer-events: none;
      }

      .btn-loading:after {
         content: '';
         position: absolute;
         width: 20px;
         height: 20px;
         top: 50%;
         left: 50%;
         margin-left: -10px;
         margin-top: -10px;
         border: 2px solid #ffffff;
         border-radius: 50%;
         border-top-color: transparent;
         animation: spin 1s ease-in-out infinite;
      }

      @keyframes spin {
         to {
            transform: rotate(360deg);
         }
      }

      #error-container {
         margin-bottom: 15px;
      }
   </style>
</head>

<body class="h-100">
   <div class="d-flex flex-column h-100 p-3">
      <div class="d-flex flex-column flex-grow-1">
         <div class="row h-100">
            <div class="col-xxl-12">
               <div class="row justify-content-center h-100">
                  <div class="col-lg-4">
                     <div class="py-lg-2" style="padding: 30px;border-radius: 10px; box-shadow:0px 0px 10px 0px rgb(0 0 0 / 6%); background: #ffffff;">
                        <!-- Error Container for Form Errors -->
                        <div id="error-container"></div>

                        @if($errors->any())
                        <br>
                        <div class="alert alert-danger">
                           <p><strong>Opps Something went wrong</strong></p>
                           <ul>
                              @foreach ($errors->all() as $error)
                              <li>{{ $error }}</li>
                              @endforeach
                           </ul>
                        </div>
                        @endif
                        @if(session()->has('error'))
                        <br>
                        <div class="alert alert-danger">
                           {{ session()->get('error') }}
                        </div>
                        @endif
                        @if(session()->has('success'))
                        <br>
                        <div class="alert alert-success">
                           {{ session()->get('success') }}
                        </div>
                        @endif
                        <div class="d-flex flex-column h-100 justify-content-center">
                           <div class="auth-logo mb-3">
                              <a href="{{route('login')}}" class="logo-dark">
                                 <img src="{{asset('backend/assets/logo.png')}}" height="100" alt="logo dark">
                              </a>
                              <a href="{{route('login')}}" class="logo-light">
                                 <img src="{{asset('backend/assets/logo.png')}}" height="100" alt="logo light">
                              </a>
                           </div>
                           <h2 class="fw-bold fs-24">Sign In</h2>
                           <p class="text-muted mt-1 mb-2">Enter your email address or user id and password to access admin panel.</p>
                           <div class="mb-2">
                              <form action="{{route('login')}}" class="authentication-form" method="post" id="loginForm">
                                 @csrf
                                 <input type="hidden" name="login_method" id="loginMethod" value="password">
                                 <div class="mb-3">
                                    <label class="form-label" for="example-email">Email Or User id</label>
                                    <div class="input-group">
                                       <input type="text" name="email" id="emailInput" class="form-control" placeholder="Enter your email or user id's">
                                       <button type="button" class="btn btn-outline-primary" id="sendOtpBtn" style="display: none;">Send OTP</button>
                                    </div>
                                 </div>

                                 <!-- Password Login Section -->
                                 <div class="mb-3" id="passwordSection">
                                    <a href="{{route('forget.password')}}" class="float-end text-muted text-unline-dashed ms-1">Reset password</a>
                                    <label class="form-label" for="example-password">Password</label>
                                    <input type="password" name="password" class="form-control" placeholder="Enter your password">
                                 </div>

                                 <!-- OTP Login Section -->
                                 <div class="mb-3 otp-section" id="otpSection">
                                    <label class="form-label" for="otp">Enter OTP</label>
                                    <div class="otp-input-group mb-2">
                                       <input type="text" name="otp[]" class="form-control otp-input" maxlength="1" data-index="0">
                                       <input type="text" name="otp[]" class="form-control otp-input" maxlength="1" data-index="1">
                                       <input type="text" name="otp[]" class="form-control otp-input" maxlength="1" data-index="2">
                                       <input type="text" name="otp[]" class="form-control otp-input" maxlength="1" data-index="3">
                                       <input type="text" name="otp[]" class="form-control otp-input" maxlength="1" data-index="4">
                                       <input type="text" name="otp[]" class="form-control otp-input" maxlength="1" data-index="5">
                                    </div>
                                    <div class="resend-otp">
                                       <a id="resendOtp" class="text-muted">Resend OTP in <span id="countdown" class="countdown">60</span> seconds</a>
                                    </div>
                                 </div>

                                 <div class="login-options">
                                    <a id="switchToOtp">Login with OTP instead</a>
                                    <a id="switchToPassword" style="display: none;">Login with Password instead</a>
                                 </div>

                                 <div class="mb-1 text-center d-grid">
                                    <button class="btn btn-soft-primary" type="submit" id="submitBtn">Sign In</button>
                                 </div>
                              </form>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
   <script src="{{asset('backend/assets/js/vendor.js')}}"></script>
   <script src="{{asset('backend/assets/js/app.js')}}"></script>
   <script>
        const generateOtpUrl = '{{ route("generate.otp") }}';
    </script>
   <script src="{{ asset('backend/assets/js/pages/login.js') }}"></script>
</body>

</html>