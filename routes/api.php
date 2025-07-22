<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;

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

// Authentication routes
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
