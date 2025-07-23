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

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// User profile routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [App\Http\Controllers\UserController::class, 'show']);
    Route::put('/user', [App\Http\Controllers\UserController::class, 'update']);
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
