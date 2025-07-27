<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SubscriptionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::get('/features', [App\Http\Controllers\FeatureController::class, 'index']);
Route::get('/subscriptions/packages', [SubscriptionController::class, 'getPackages']);

// Stripe webhook route (no auth required)
Route::post('/stripe/webhook', [App\Http\Controllers\StripeWebhookController::class, 'handleWebhook']);

// Email verification route
Route::get('/verify-email/{hash}', [App\Http\Controllers\Auth\VerifyEmailController::class, '__invoke'])
    ->middleware(['throttle:6,1'])
    ->name('verification.verify');

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// User profile routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [App\Http\Controllers\UserController::class, 'show']);
    Route::put('/user', [App\Http\Controllers\UserController::class, 'update']);
    Route::post('/user', [App\Http\Controllers\UserController::class, 'update']); // Alternative for file uploads
    Route::put('/user/password', [App\Http\Controllers\UserController::class, 'changePassword']);
});

// Admin routes (protected)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index']);
    Route::post('/admin/users', [UserController::class, 'store']);
    Route::put('/admin/users/{user}', [UserController::class, 'updateUser']);
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy']);
    Route::put('/admin/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
    
    // Get users for assistant assignment
    Route::get('/admin/users/for-assignment', [UserController::class, 'getUsersForAssignment']);
});

// Assistant routes (protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('assistants')->group(function () {
        Route::get('/', [App\Http\Controllers\AssistantController::class, 'index']);
        Route::post('/', [App\Http\Controllers\AssistantController::class, 'store']);
        Route::get('/{assistantId}', [App\Http\Controllers\AssistantController::class, 'show']);
        Route::put('/{assistantId}', [App\Http\Controllers\AssistantController::class, 'update']);
        Route::delete('/{assistantId}', [App\Http\Controllers\AssistantController::class, 'destroy']);
        Route::get('/{assistantId}/stats', [App\Http\Controllers\AssistantController::class, 'stats']);
    });
    
    // Admin assistant routes
    Route::middleware('admin')->prefix('admin/assistants')->group(function () {
        Route::get('/', [App\Http\Controllers\AssistantController::class, 'adminIndex']);
    });
    
    // Twilio phone number routes
    Route::prefix('twilio')->group(function () {
        Route::get('/available-numbers', [App\Http\Controllers\TwilioController::class, 'getAvailableNumbers']);
        Route::post('/purchase-number', [App\Http\Controllers\TwilioController::class, 'purchaseNumber']);
        Route::get('/purchased-numbers', [App\Http\Controllers\TwilioController::class, 'getPurchasedNumbers']);
        Route::delete('/release-number', [App\Http\Controllers\TwilioController::class, 'releaseNumber']);
    });
});

// Dashboard routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard/stats', function (Request $request) {
        $user = $request->user();
        $assistants = \App\Models\Assistant::where('user_id', $user->id)->count();
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_assistants' => $assistants,
                'recent_activity' => [],
                'quick_stats' => [
                    'assistants' => $assistants,
                    'calls_today' => 0,
                    'total_calls' => 0
                ]
            ]
        ]);
    });
    
    Route::get('/dashboard/activity', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => []
        ]);
    });
});

// Admin dashboard routes
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin/dashboard/stats', function (Request $request) {
        $totalUsers = \App\Models\User::count();
        $totalAssistants = \App\Models\Assistant::count();
        $activeUsers = \App\Models\User::where('status', 'active')->count();
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'total_assistants' => $totalAssistants,
                'active_users' => $activeUsers,
                'quick_stats' => [
                    'users' => $totalUsers,
                    'assistants' => $totalAssistants,
                    'active_users' => $activeUsers
                ]
            ]
        ]);
    });
    
    Route::get('/admin/dashboard/activity', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => []
        ]);
    });
});

// Subscription routes (protected)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/subscriptions/current', [SubscriptionController::class, 'getCurrentSubscription']);
    Route::post('/subscriptions/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::post('/subscriptions/cancel', [SubscriptionController::class, 'cancel']);
    Route::post('/subscriptions/upgrade', [SubscriptionController::class, 'upgrade']);
    Route::get('/subscriptions/usage', [SubscriptionController::class, 'getUsage']);
    
    // Stripe routes
    Route::prefix('stripe')->group(function () {
        Route::post('/payment-intent', [App\Http\Controllers\StripeController::class, 'createPaymentIntent']);
        Route::post('/subscription', [App\Http\Controllers\StripeController::class, 'createSubscription']);
        Route::post('/subscription/cancel', [App\Http\Controllers\StripeController::class, 'cancelSubscription']);
        Route::post('/subscription/update', [App\Http\Controllers\StripeController::class, 'updateSubscription']);
        Route::get('/subscription/details', [App\Http\Controllers\StripeController::class, 'getSubscriptionDetails']);
    });
    
    // Transaction routes
    Route::prefix('transactions')->group(function () {
        Route::get('/', [App\Http\Controllers\TransactionController::class, 'index']);
        Route::post('/', [App\Http\Controllers\TransactionController::class, 'store']);
        Route::get('/{transactionId}', [App\Http\Controllers\TransactionController::class, 'show']);
        Route::put('/{transactionId}/status', [App\Http\Controllers\TransactionController::class, 'updateStatus']);
        Route::post('/{transactionId}/process', [App\Http\Controllers\TransactionController::class, 'processPayment']);
    });
});

// Admin subscription routes (protected)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::prefix('admin/subscriptions')->group(function () {
        Route::get('/', [App\Http\Controllers\SubscriptionController::class, 'adminGetSubscriptions']);
        Route::get('/packages', [App\Http\Controllers\SubscriptionController::class, 'adminGetPackages']);
        Route::post('/packages', [App\Http\Controllers\SubscriptionController::class, 'adminCreatePackage']);
        Route::put('/packages/{id}', [App\Http\Controllers\SubscriptionController::class, 'adminUpdatePackage']);
        Route::delete('/packages/{id}', [App\Http\Controllers\SubscriptionController::class, 'adminDeletePackage']);
    });
    
    // Admin transaction routes
    Route::prefix('admin/transactions')->group(function () {
        Route::get('/', [App\Http\Controllers\TransactionController::class, 'adminIndex']);
        Route::get('/stats', [App\Http\Controllers\TransactionController::class, 'adminStats']);
    });

    // Admin feature routes
    Route::prefix('admin/features')->group(function () {
        Route::get('/', [App\Http\Controllers\FeatureController::class, 'adminIndex']);
        Route::post('/', [App\Http\Controllers\FeatureController::class, 'store']);
        Route::put('/{id}', [App\Http\Controllers\FeatureController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\FeatureController::class, 'destroy']);
    });
    
    // Admin users routes
    Route::get('/admin/users', function (Request $request) {
        $users = \App\Models\User::select('id', 'name', 'email', 'role', 'status')
            ->orderBy('name')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    });
});

// Settings routes (admin only)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::prefix('admin/settings')->group(function () {
        Route::get('/', [App\Http\Controllers\SettingController::class, 'index']);
        Route::get('/{key}', [App\Http\Controllers\SettingController::class, 'show']);
        Route::put('/{key}', [App\Http\Controllers\SettingController::class, 'update']);
    });
});

// Public assistant templates endpoint
Route::get('/assistant-templates', [App\Http\Controllers\SettingController::class, 'getAssistantTemplates']);

// System settings routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/system-settings', [App\Http\Controllers\SystemSettingController::class, 'index']);
    Route::post('/system-settings', [App\Http\Controllers\SystemSettingController::class, 'update']);
});
Route::get('/public-settings', [App\Http\Controllers\SystemSettingController::class, 'getPublicSettings']);

// Test route for debugging request headers
Route::post('/test-headers', function (Request $request) {
    return response()->json([
        'success' => true,
        'headers' => $request->headers->all(),
        'method' => $request->method(),
        'url' => $request->url(),
        'has_csrf' => $request->hasHeader('X-CSRF-TOKEN'),
        'has_authorization' => $request->hasHeader('Authorization'),
        'content_type' => $request->header('Content-Type')
    ]);
});

// Temporary Twilio diagnostic route
Route::get('/twilio/diagnostics', function () {
    $twilioService = app(\App\Services\TwilioService::class);
    $results = $twilioService->runDiagnostics();
    
    return response()->json([
        'success' => true,
        'data' => $results,
        'message' => 'Twilio diagnostics completed'
    ]);
})->middleware('auth:sanctum');

// Demo request routes
Route::post('/demo-request', [App\Http\Controllers\DemoRequestController::class, 'store']);
Route::post('/demo-request/check', [App\Http\Controllers\DemoRequestController::class, 'checkExistingRequest']);

// Admin demo request routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/demo-requests', [App\Http\Controllers\DemoRequestController::class, 'adminIndex']);
    Route::get('/demo-requests/stats', [App\Http\Controllers\DemoRequestController::class, 'adminStats']);
    Route::get('/demo-requests/{demoRequest}', [App\Http\Controllers\DemoRequestController::class, 'show']);
    Route::patch('/demo-requests/{demoRequest}/status', [App\Http\Controllers\DemoRequestController::class, 'updateStatus']);
    Route::delete('/demo-requests/{demoRequest}', [App\Http\Controllers\DemoRequestController::class, 'destroy']);
});
