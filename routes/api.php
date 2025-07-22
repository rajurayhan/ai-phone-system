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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

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

// Test route for Vapi connection
Route::get('/test-vapi', function () {
    $vapiService = app(App\Services\VapiService::class);
    $assistants = $vapiService->getAssistants();
    return response()->json([
        'success' => true,
        'data' => $assistants,
        'count' => count($assistants)
    ]);
});

// Test route for profile update debugging
Route::middleware('auth:sanctum')->get('/test-profile', function (Request $request) {
    return response()->json([
        'success' => true,
        'user' => $request->user(),
        'message' => 'Profile test route working'
    ]);
});
