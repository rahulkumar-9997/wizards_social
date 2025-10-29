<?php
// use App\Livewire\Backend\Dashboard;
// use App\Livewire\Backend\Database;
// use App\Livewire\Backend\Facebook\Index;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\LoginController;
use App\Http\Controllers\Backend\ForgotPasswordController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\UsersController;
use App\Http\Controllers\Backend\RolesController;
use App\Http\Controllers\Backend\PermissionsController;
use App\Http\Controllers\Backend\DatabaseController;
use App\Http\Controllers\Backend\CacheController;
use App\Http\Controllers\Backend\SocialController;
use App\Http\Controllers\Backend\DataController;
use App\Http\Controllers\Backend\AdsController;
use App\Http\Controllers\Backend\FacebookController;
use App\Http\Controllers\Backend\InstagramController;
use App\Http\Controllers\Backend\YoutubeController;


Route::get('/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy.policy');

Route::get('/', [LoginController::class, 'showLoginForm']);
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login.form');
Route::post('login', [LoginController::class, 'login'])->name('login');
Route::post('generate-otp', [LoginController::class, 'generateOtp'])->name('generate.otp');
Route::get('forget/password', [ForgotPasswordController::class, 'showForgetPasswordForm'])->name('forget.password');
Route::post('forget.password', [ForgotPasswordController::class, 'submitForgetPasswordForm'])->name('forget.password.submit');
Route::get('reset-password/{token}', [ForgotPasswordController::class, 'showResetPasswordForm'])->name('reset.password.get');
Route::post('reset-password', [ForgotPasswordController::class, 'submitResetPasswordForm'])->name('reset.password.post');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');
Route::group(['middleware' => ['auth']], function() {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::get('clear-cache', [CacheController::class, 'clearCache'])->name('clear-cache');
    Route::get('database-management', [DatabaseController::class, 'showTables'])->name('show.tables');
    Route::post('truncate-tables', [DatabaseController::class, 'truncateTables'])->name('truncate.tables');
    Route::get('backup-database', [DatabaseController::class, 'backupDatabase'])->name('backup.database');

    Route::group(['prefix' => 'users', 'as' => 'users.'], function() {
        Route::get('/', [UsersController::class, 'index'])->name('index');
        Route::get('/create', [UsersController::class, 'create'])->name('create');
        Route::post('/', [UsersController::class, 'store'])->name('store');
        Route::get('/{user}', [UsersController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UsersController::class, 'edit'])->name('edit');
        Route::patch('/{user}', [UsersController::class, 'update'])->name('update');
        Route::delete('/{user}', [UsersController::class, 'destroy'])->name('destroy');
        Route::get('/profile', [UsersController::class, 'UserProfile'])->name('profile');
        Route::get('/profile/{id}/edit', [UsersController::class, 'UserProfileEditForm'])->name('profile.edit');
        Route::post('/profile/{id}/update', [UsersController::class, 'UserProfileEditFormSubmit'])->name('profile.update');
    });
    Route::resource('roles', RolesController::class);
    Route::resource('permissions', PermissionsController::class);
    
    Route::get('/facebook', [FacebookController::class, 'index'])->name('facebook.index');    

    Route::get('/youtube', [YoutubeController::class, 'index'])->name('youtube.index');
    Route::prefix('social')->name('social.')->group(function () {
        Route::get('/{provider}/redirect', [SocialController::class, 'redirect'])
            ->where('provider', 'facebook|google')
            ->name('redirect');
        Route::get('/{provider}/callback', [SocialController::class, 'callback'])
            ->where('provider', 'facebook|google')
            ->name('callback');
        Route::delete('/{provider}/disconnect/{accountId?}', [SocialController::class, 'disconnect'])
    ->where('provider', 'facebook|google|instagram')
    ->name('disconnect');

    });
    Route::get('/facebook/refresh-token', [FacebookController::class, 'refreshToken'])
    ->name('facebook.refresh.token');
    Route::get('/instagram', [InstagramController::class, 'index'])->name('instagram.index');
    Route::get('instagram/{id}', [InstagramController::class, 'show'])->name('instagram.show');
    Route::get('/instagram/fetch/{id}', [InstagramController::class, 'fetchHtml'])
    ->name('instagram.fetch.html');

    Route::get('/instagram/{id}/post/{postId}/insights-page', [InstagramController::class, 'postInsightsPage'])->name('instagram.post.insights.page');

    
    Route::get('/instagram/{mediaId}/comments/html', [InstagramController::class, 'fetchCommentsHtml'])->name('instagram.comments.html');
   
    Route::get('/instagram/{id}/metrics-graph', [InstagramController::class, 'metricsGraph'])
    ->name('instagram.metrics.graph');

    Route::get('instagram/{id}/insights', [InstagramController::class, 'insights'])->name('instagram.insights');

    // Platform Specific Data Routes
    // Route::prefix('platform')->name('platform.')->group(function () {
    //     Route::get('/facebook/accounts', [FacebookController::class, 'getAccounts'])->name('facebook.accounts');
    //     Route::get('/facebook/posts', [FacebookController::class, 'getPosts'])->name('facebook.posts');
    //     Route::get('/facebook/insights', [FacebookController::class, 'getInsights'])->name('facebook.insights');
    //     Route::get('/instagram/accounts', [InstagramController::class, 'getAccounts'])->name('instagram.accounts');
    //     Route::get('/instagram/posts', [InstagramController::class, 'getPosts'])->name('instagram.posts');
    //     Route::get('/instagram/insights', [InstagramController::class, 'getInsights'])->name('instagram.insights');
    //     Route::get('/youtube/channels', [YoutubeController::class, 'getChannels'])->name('youtube.channels');
    //     Route::get('/youtube/videos', [YoutubeController::class, 'getVideos'])->name('youtube.videos');
    //     Route::get('/youtube/analytics', [YoutubeController::class, 'getAnalytics'])->name('youtube.analytics');
    // });

    // Insights & Analytics Routes
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/ads/{adAccountId}/insights', [AdsController::class, 'insights'])->name('ads.insights');
        Route::get('/instagram/{socialAccountId}/insights', [FacebookController::class, 'getInstagramInsights'])->name('instagram.insights');
        Route::get('/youtube/{socialAccountId}/demographics', [YoutubeController::class, 'getDemographics'])->name('youtube.demographics');
    });

    

});