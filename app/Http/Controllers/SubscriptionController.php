<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPackage;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    /**
     * Get all available subscription packages
     */
    public function getPackages(): JsonResponse
    {
        $packages = SubscriptionPackage::active()
            ->orderBy('price')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $packages
        ]);
    }

    /**
     * Get user's current subscription
     */
    public function getCurrentSubscription(): JsonResponse
    {
        $user = Auth::user();
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            // Check if there are any subscriptions for this user
            $anySubscription = $user->subscriptions()->latest()->first();
            
            if ($anySubscription) {
                return response()->json([
                    'success' => true,
                    'data' => $anySubscription->load('package'),
                    'message' => 'Latest subscription found (may not be active)'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No subscription found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $subscription->load('package')
        ]);
    }

    /**
     * Subscribe user to a package
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'package_id' => 'required|exists:subscription_packages,id'
        ]);

        $user = Auth::user();
        $package = SubscriptionPackage::findOrFail($request->package_id);

        // Check if user already has an active subscription
        if ($user->hasActiveSubscription()) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active subscription'
            ], 400);
        }

        // Create subscription (no trial period)
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_package_id' => $package->id,
            'status' => 'active',
            'current_period_start' => Carbon::now(),
            'current_period_end' => Carbon::now()->addMonth(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $subscription->load('package'),
            'message' => 'Subscription created successfully.'
        ], 201);
    }

    /**
     * Cancel user's subscription
     */
    public function cancel(): JsonResponse
    {
        $user = Auth::user();
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found'
            ], 404);
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now(),
            'ends_at' => $subscription->current_period_end,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully'
        ]);
    }

    /**
     * Upgrade user's subscription
     */
    public function upgrade(Request $request): JsonResponse
    {
        $request->validate([
            'package_id' => 'required|exists:subscription_packages,id'
        ]);

        $user = Auth::user();
        $newPackage = SubscriptionPackage::findOrFail($request->package_id);
        $currentSubscription = $user->activeSubscription;

        if (!$currentSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found'
            ], 404);
        }

        $currentPackage = $currentSubscription->package;

        // Check if it's actually an upgrade
        if ($newPackage->price <= $currentPackage->price) {
            return response()->json([
                'success' => false,
                'message' => 'You can only upgrade to a higher tier'
            ], 400);
        }

        // Update subscription to new package
        $currentSubscription->update([
            'subscription_package_id' => $newPackage->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $currentSubscription->load('package'),
            'message' => 'Subscription upgraded successfully'
        ]);
    }

    /**
     * Get subscription usage statistics
     */
    public function getUsage(): JsonResponse
    {
        $user = Auth::user();
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found'
            ], 404);
        }

        $package = $subscription->package;
        $assistantsCount = $user->assistants()->count();
        $remainingAssistants = $user->remaining_assistants;

        $usage = [
            'assistants' => [
                'used' => $assistantsCount,
                'limit' => $package->formatted_voice_agents_limit,
                'remaining' => $remainingAssistants,
                'is_unlimited' => $package->isUnlimited('voice_agents_limit'),
            ],
            'subscription' => [
                'status' => $subscription->status,
                'current_period_end' => $subscription->current_period_end,
                'days_remaining' => $subscription->daysUntilExpiration(),
            ],
            'package' => [
                'name' => $package->name,
                'price' => $package->formatted_price,
                'description' => $package->description,
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $usage
        ]);
    }

    /**
     * Admin: Get all subscriptions with filtering
     */
    public function adminGetSubscriptions(Request $request): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $query = UserSubscription::with(['user', 'package']);

        // Apply filters - handle null and 'null' values
        if ($request->filled('status') && $request->status !== 'null' && $request->status !== null) {
            $query->where('status', $request->status);
        }

        if ($request->filled('package_id') && $request->package_id !== 'null' && $request->package_id !== null) {
            $query->where('subscription_package_id', $request->package_id);
        }

        if ($request->filled('search') && $request->search !== 'null' && $request->search !== null) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_range') && $request->date_range !== 'null' && $request->date_range !== null) {
            $dateRange = $request->date_range;
            $now = Carbon::now();
            
            switch ($dateRange) {
                case 'today':
                    $query->whereDate('created_at', $now->toDateString());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [$now->startOfWeek()->toDateTimeString(), $now->endOfWeek()->toDateTimeString()]);
                    break;
                case 'month':
                    $query->whereBetween('created_at', [$now->startOfMonth()->toDateTimeString(), $now->endOfMonth()->toDateTimeString()]);
                    break;
                case 'quarter':
                    $query->whereBetween('created_at', [$now->startOfQuarter()->toDateTimeString(), $now->endOfQuarter()->toDateTimeString()]);
                    break;
                case 'year':
                    $query->whereBetween('created_at', [$now->startOfYear()->toDateTimeString(), $now->endOfYear()->toDateTimeString()]);
                    break;
            }
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $subscriptions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Create a clone of the query for summary calculations
        $summaryQuery = clone $query;
        $summaryQuery->getQuery()->orders = null; // Remove ordering for summary
        $summaryQuery->getQuery()->limit = null; // Remove limit for summary
        $summaryQuery->getQuery()->offset = null; // Remove offset for summary

        // Get statistics based on filtered query
        $stats = [
            'total' => $summaryQuery->count(),
            'active' => $summaryQuery->where('status', 'active')->count(),
            'pending' => $summaryQuery->where('status', 'pending')->count(),
            'cancelled' => $summaryQuery->where('status', 'cancelled')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $subscriptions->items(),
            'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
            ],
            'stats' => $stats,
            'debug' => [
                'filters_applied' => [
                    'status' => $request->get('status'),
                    'package_id' => $request->get('package_id'),
                    'search' => $request->get('search'),
                    'date_range' => $request->get('date_range')
                ]
            ]
        ]);
    }

    /**
     * Admin: Get all packages
     */
    public function adminGetPackages(): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $packages = SubscriptionPackage::orderBy('price')->get();

        return response()->json([
            'success' => true,
            'data' => $packages
        ]);
    }

    /**
     * Admin: Create a new package
     */
    public function adminCreatePackage(Request $request): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:subscription_packages,slug',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'voice_agents_limit' => 'required|integer',
            'monthly_minutes_limit' => 'required|integer',
            'features' => 'nullable|string',
            'support_level' => 'required|string|in:email,priority,dedicated',
            'analytics_level' => 'required|string|in:basic,advanced,custom',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $package = SubscriptionPackage::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $package,
            'message' => 'Package created successfully'
        ], 201);
    }

    /**
     * Admin: Update a package
     */
    public function adminUpdatePackage(Request $request, $id): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:subscription_packages,slug,' . $id,
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'voice_agents_limit' => 'required|integer',
            'monthly_minutes_limit' => 'required|integer',
            'features' => 'nullable|string',
            'support_level' => 'required|string|in:email,priority,dedicated',
            'analytics_level' => 'required|string|in:basic,advanced,custom',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $package = SubscriptionPackage::findOrFail($id);
        $package->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $package,
            'message' => 'Package updated successfully'
        ]);
    }

    /**
     * Admin: Delete a package
     */
    public function adminDeletePackage($id): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $package = SubscriptionPackage::findOrFail($id);
        
        // Check if package has active subscriptions
        $activeSubscriptions = UserSubscription::where('subscription_package_id', $id)
            ->whereIn('status', ['active', 'trial', 'pending'])
            ->count();

        if ($activeSubscriptions > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete package with active subscriptions'
            ], 400);
        }

        $package->delete();

        return response()->json([
            'success' => true,
            'message' => 'Package deleted successfully'
        ]);
    }
}
