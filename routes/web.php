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

// Test route for database data verification
Route::get('/test-database-data', function () {
    $transactions = \App\Models\Transaction::with(['user', 'package'])->get();
    $subscriptions = \App\Models\UserSubscription::with(['user', 'package'])->get();
    
    return response()->json([
        'transactions' => [
            'count' => $transactions->count(),
            'data' => $transactions->map(function($t) {
                return [
                    'id' => $t->id,
                    'user_id' => $t->user_id,
                    'user_name' => $t->user->name ?? 'Unknown',
                    'amount' => $t->amount,
                    'status' => $t->status,
                    'type' => $t->type,
                    'created_at' => $t->created_at
                ];
            })
        ],
        'subscriptions' => [
            'count' => $subscriptions->count(),
            'data' => $subscriptions->map(function($s) {
                return [
                    'id' => $s->id,
                    'user_id' => $s->user_id,
                    'user_name' => $s->user->name ?? 'Unknown',
                    'status' => $s->status,
                    'package_name' => $s->package->name ?? 'Unknown',
                    'created_at' => $s->created_at
                ];
            })
        ]
    ]);
});

// Test route for null value handling
Route::get('/test-null-filters', function () {
    $request = request();
    
    return response()->json([
        'test_filters' => [
            'status' => [
                'value' => $request->get('status'),
                'filled' => $request->filled('status'),
                'is_null' => $request->get('status') === null,
                'is_string_null' => $request->get('status') === 'null'
            ],
            'payment_method' => [
                'value' => $request->get('payment_method'),
                'filled' => $request->filled('payment_method'),
                'is_null' => $request->get('payment_method') === null,
                'is_string_null' => $request->get('payment_method') === 'null'
            ],
            'user_id' => [
                'value' => $request->get('user_id'),
                'filled' => $request->filled('user_id'),
                'is_null' => $request->get('user_id') === null,
                'is_string_null' => $request->get('user_id') === 'null'
            ]
        ],
        'all_request_params' => $request->all()
    ]);
});

// Test route for subscription period verification
Route::get('/test-subscription-periods', function () {
    $subscriptions = \App\Models\UserSubscription::with(['user', 'package'])->get();
    
    return response()->json([
        'subscriptions' => $subscriptions->map(function($s) {
            return [
                'id' => $s->id,
                'user_name' => $s->user->name ?? 'Unknown',
                'package_name' => $s->package->name ?? 'Unknown',
                'status' => $s->status,
                'current_period_start' => $s->current_period_start ? $s->current_period_start->toISOString() : null,
                'current_period_end' => $s->current_period_end ? $s->current_period_end->toISOString() : null,
                'trial_ends_at' => $s->trial_ends_at ? $s->trial_ends_at->toISOString() : null,
                'stripe_subscription_id' => $s->stripe_subscription_id,
                'days_remaining' => $s->current_period_end ? now()->diffInDays($s->current_period_end, false) : null,
                'is_active' => $s->status === 'active',
                'is_expired' => $s->current_period_end ? $s->current_period_end->isPast() : false
            ];
        })
    ]);
});
