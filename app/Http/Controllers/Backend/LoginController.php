<?php
namespace App\Http\Controllers\Backend;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Mail\OtpMail;
use App\Models\User;
class LoginController extends Controller
{
    // https://dev.to/codeanddeploy/laravel-8-user-roles-and-permissions-step-by-step-tutorial-1dij
    public function showLoginForm(Request $request){
        return view('backend.PAGES.auth.index');
    }

    public function login(Request $request)
    {       
        $loginMethod = $request->input('login_method', 'password');        
        if ($loginMethod === 'otp') {
            return $this->loginWithOtp($request);
        } else {
            return $this->loginWithPassword($request);
        }
    }
    
    private function loginWithPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string|min:8',
        ]);        
        $login = $request->input('email');
        $password = $request->input('password');
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'user_id';        
        if (auth()->attempt([$fieldType => $login, 'password' => $password])) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'You have successfully signed in!',
                    'redirect' => url('facebook')
                ]);
            }
            return redirect()->intended('facebook')->withSuccess('You have successfully signed in!');
        } else {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Oops! Invalid login credentials.'
                ], 401);
            }
            return redirect()->back()->with('error', 'Oops! Invalid login credentials.');
        }
    }
    
    private function loginWithOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|string|size:6',
        ]);        
        $email = $request->input('email');
        $otp = $request->input('otp_code');
        $cachedOtp = Cache::get('otp_' . $email);        
        if (!$cachedOtp || $cachedOtp !== $otp) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP.'
                ], 401);
            }
            return redirect()->back()->with('error', 'Invalid or expired OTP.');
        }
        $user = User::where('email', $email)->first();        
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No account found with this email.'
                ], 404);
            }
            return redirect()->back()->with('error', 'No account found with this email.');
        }
        Auth::login($user);
        Cache::forget('otp_' . $email);        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'You have successfully signed in with OTP!',
                'redirect' => url('dashboard')
            ]);
        }
        return redirect()->intended('dashboard')->withSuccess('You have successfully signed in with OTP!');
    }
    
    public function generateOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);        
        $email = $request->input('email');
        $user = User::where('email', $email)->first();        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this email.'
            ], 404);
        }
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put('otp_' . $email, $otp, 600);
        
        try {             
            Mail::to($email)->send(new OtpMail($otp));           
            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('OTP Send Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP. Please try again.'
            ], 500);
        }
    }

    public function logout() {
        Session::flush();
        Auth::logout();
        return redirect()->route('login')->with('success', 'Logged out successfully');
    }
}
