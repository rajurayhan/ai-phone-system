<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/test-filtering', function () {
    return view('test-filtering');
});

Route::get('/debug', function () {
    return view('debug');
});

// Serve the SPA for all routes except API routes
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*$');

// Test route for Stripe environment variable
Route::get('/test-stripe-env', function () {
    return response()->json([
        'stripe_key' => env('MIX_STRIPE_PUBLISHABLE_KEY') ? 'Set' : 'Not Set',
        'key_length' => strlen(env('MIX_STRIPE_PUBLISHABLE_KEY', '')),
        'key_prefix' => substr(env('MIX_STRIPE_PUBLISHABLE_KEY', ''), 0, 20)
    ]);
});

// Test page for Stripe frontend
Route::get('/test-stripe-page', function () {
    return view('test-stripe');
});

// Test route for Stripe subscription creation (for debugging)
Route::get('/test-stripe-subscription', function () {
    $user = \App\Models\User::first();
    $package = \App\Models\SubscriptionPackage::first();
    
    if (!$user || !$package) {
        return response()->json(['error' => 'User or package not found']);
    }
    
    $stripeService = app(\App\Services\StripeService::class);
    
    try {
        // Test customer creation
        $customerId = $stripeService->createCustomer($user);
        
        return response()->json([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'customer_created' => $customerId ? 'Yes' : 'No',
            'customer_id' => $customerId,
            'package_price' => $package->price,
            'package_name' => $package->name
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});

// Test route for Stripe customer operations
Route::get('/test-stripe-customer', function () {
    $user = \App\Models\User::first();
    
    if (!$user) {
        return response()->json(['error' => 'User not found']);
    }
    
    try {
        $stripe = new \Stripe\StripeClient(config('stripe.secret_key'));
        
        // Create a test customer
        $customer = $stripe->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
                'test' => 'true'
            ]
        ]);
        
        return response()->json([
            'success' => true,
            'customer_id' => $customer->id,
            'customer_email' => $customer->email,
            'message' => 'Customer created successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'stripe_error' => $e instanceof \Stripe\Exception\ApiErrorException ? $e->getStripeCode() : null
        ]);
    }
});

// Test route for Stripe API version and parameters
Route::get('/test-stripe-api', function () {
    try {
        $stripe = new \Stripe\StripeClient(config('stripe.secret_key'));
        
        // Test API version
        $account = $stripe->accounts->retrieve();
        
        return response()->json([
            'success' => true,
            'api_version' => config('stripe.api_version'),
            'account_id' => $account->id,
            'message' => 'Stripe API connection successful'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'api_version' => config('stripe.api_version'),
            'stripe_error' => $e instanceof \Stripe\Exception\ApiErrorException ? $e->getStripeCode() : null
        ]);
    }
});
