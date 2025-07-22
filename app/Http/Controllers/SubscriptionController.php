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
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found'
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

        // Create subscription (for now, we'll create a trial subscription)
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_package_id' => $package->id,
            'status' => 'trial',
            'trial_ends_at' => Carbon::now()->addDays(14), // 14-day trial
            'current_period_start' => Carbon::now(),
            'current_period_end' => Carbon::now()->addDays(14),
        ]);

        return response()->json([
            'success' => true,
            'data' => $subscription->load('package'),
            'message' => 'Subscription created successfully. You are now on a 14-day trial.'
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
                'is_on_trial' => $subscription->isOnTrial(),
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
     * Admin: Get all subscriptions
     */
    public function adminGetSubscriptions(): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $subscriptions = UserSubscription::with(['user', 'package'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subscriptions
        ]);
    }
}
