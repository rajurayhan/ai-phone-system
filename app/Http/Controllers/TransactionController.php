<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\UserSubscription;
use App\Models\SubscriptionPackage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TransactionController extends Controller
{
    /**
     * Get user's transaction history
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = Transaction::where('user_id', $user->id)
            ->with(['package', 'subscription'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_range')) {
            $dateRange = $request->date_range;
            $now = \Carbon\Carbon::now();
            
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
        $perPage = $request->get('per_page', 15);
        $transactions = $query->paginate($perPage);

        // Create a clone of the query for summary calculations
        $summaryQuery = clone $query;
        $summaryQuery->getQuery()->orders = null; // Remove ordering for summary
        $summaryQuery->getQuery()->limit = null; // Remove limit for summary
        $summaryQuery->getQuery()->offset = null; // Remove offset for summary

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'summary' => [
                'total_transactions' => $summaryQuery->count(),
                'total_amount' => $summaryQuery->where('status', 'completed')->sum('amount'),
                'pending_transactions' => $summaryQuery->where('status', 'pending')->count(),
                'failed_transactions' => $summaryQuery->where('status', 'failed')->count(),
            ]
        ]);
    }

    /**
     * Get a specific transaction
     */
    public function show(Request $request, $transactionId): JsonResponse
    {
        $user = Auth::user();
        
        $transaction = Transaction::where('user_id', $user->id)
            ->where('transaction_id', $transactionId)
            ->with(['package', 'subscription', 'user'])
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }

    /**
     * Create a new transaction
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'package_id' => 'required|exists:subscription_packages,id',
            'payment_method' => 'required|in:stripe,paypal,manual',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'type' => 'required|in:subscription,upgrade,renewal,refund,trial',
            'billing_email' => 'required|email',
            'billing_name' => 'nullable|string|max:255',
            'billing_address' => 'nullable|string|max:500',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_country' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'external_transaction_id' => 'nullable|string|max:255',
            'payment_details' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $package = SubscriptionPackage::findOrFail($request->package_id);
        
        // Create transaction
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'subscription_package_id' => $package->id,
            'user_subscription_id' => $request->user_subscription_id,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'status' => Transaction::STATUS_PENDING,
            'payment_method' => $request->payment_method,
            'payment_method_id' => $request->payment_method_id,
            'payment_details' => $request->payment_details,
            'billing_email' => $request->billing_email,
            'billing_name' => $request->billing_name,
            'billing_address' => $request->billing_address,
            'billing_city' => $request->billing_city,
            'billing_state' => $request->billing_state,
            'billing_country' => $request->billing_country,
            'billing_postal_code' => $request->billing_postal_code,
            'type' => $request->type,
            'description' => $request->description,
            'external_transaction_id' => $request->external_transaction_id,
            'metadata' => $request->metadata,
        ]);

        // If this is a subscription transaction, create a pending subscription
        if ($request->type === Transaction::TYPE_SUBSCRIPTION) {
            $this->createPendingSubscription($user, $transaction);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction->load(['package', 'subscription']),
            'message' => 'Transaction created successfully'
        ], 201);
    }

    /**
     * Update transaction status
     */
    public function updateStatus(Request $request, $transactionId): JsonResponse
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,completed,failed,refunded,cancelled',
            'external_transaction_id' => 'nullable|string|max:255',
            'payment_details' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $transaction = Transaction::where('user_id', $user->id)
            ->where('transaction_id', $transactionId)
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        $updateData = [
            'status' => $request->status,
        ];

        // Set timestamps based on status
        if ($request->status === Transaction::STATUS_COMPLETED) {
            $updateData['processed_at'] = Carbon::now();
        } elseif ($request->status === Transaction::STATUS_FAILED) {
            $updateData['failed_at'] = Carbon::now();
        }

        if ($request->has('external_transaction_id')) {
            $updateData['external_transaction_id'] = $request->external_transaction_id;
        }

        if ($request->has('payment_details')) {
            $updateData['payment_details'] = $request->payment_details;
        }

        if ($request->has('metadata')) {
            $updateData['metadata'] = $request->metadata;
        }

        $transaction->update($updateData);

        return response()->json([
            'success' => true,
            'data' => $transaction->load(['package', 'subscription']),
            'message' => 'Transaction status updated successfully'
        ]);
    }

    /**
     * Process payment (simulate payment processing)
     */
    public function processPayment(Request $request, $transactionId): JsonResponse
    {
        $user = Auth::user();
        
        $transaction = Transaction::where('user_id', $user->id)
            ->where('transaction_id', $transactionId)
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        if ($transaction->status !== Transaction::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction is not pending'
            ], 400);
        }

        // Simulate payment processing
        // In a real application, this would integrate with Stripe, PayPal, etc.
        $success = rand(1, 10) > 2; // 80% success rate for demo

        if ($success) {
            // Update transaction status
            $transaction->update([
                'status' => Transaction::STATUS_COMPLETED,
                'processed_at' => Carbon::now(),
                'external_transaction_id' => 'EXT-' . strtoupper(uniqid()),
            ]);

            // Create or update subscription if this is a subscription transaction
            if ($transaction->type === Transaction::TYPE_SUBSCRIPTION) {
                $this->createOrUpdateSubscription($user, $transaction);
            }

            return response()->json([
                'success' => true,
                'data' => $transaction->load(['package', 'subscription']),
                'message' => 'Payment processed successfully and subscription activated!'
            ]);
        } else {
            $transaction->update([
                'status' => Transaction::STATUS_FAILED,
                'failed_at' => Carbon::now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed'
            ], 400);
        }
    }

    /**
     * Create or update subscription based on transaction
     */
    private function createOrUpdateSubscription($user, $transaction): void
    {
        // Cancel any existing active subscription
        $existingSubscription = $user->activeSubscription;
        if ($existingSubscription) {
            $existingSubscription->update([
                'status' => 'cancelled',
                'cancelled_at' => Carbon::now(),
                'ends_at' => $existingSubscription->current_period_end,
            ]);
        }

        // Create new subscription
        $package = $transaction->package;
        $trialDays = 14; // 14-day trial
        $currentPeriodStart = Carbon::now();
        $currentPeriodEnd = Carbon::now()->addDays($trialDays);

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_package_id' => $package->id,
            'status' => 'active',
            'current_period_start' => $currentPeriodStart,
            'current_period_end' => $currentPeriodEnd,
            'trial_ends_at' => $currentPeriodEnd,
            'created_by' => $user->id,
        ]);

        // Update transaction with subscription reference
        $transaction->update([
            'user_subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Create a pending subscription for a new transaction.
     * This is used when a subscription transaction is created but payment is not yet processed.
     */
    private function createPendingSubscription($user, $transaction): void
    {
        // Cancel any existing active subscription
        $existingSubscription = $user->activeSubscription;
        if ($existingSubscription) {
            $existingSubscription->update([
                'status' => 'cancelled',
                'cancelled_at' => Carbon::now(),
                'ends_at' => $existingSubscription->current_period_end,
            ]);
        }

        // Create new pending subscription
        $package = $transaction->package;
        $trialDays = 14; // 14-day trial
        $currentPeriodStart = Carbon::now();
        $currentPeriodEnd = Carbon::now()->addDays($trialDays);

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_package_id' => $package->id,
            'status' => 'pending', // Set status to pending
            'current_period_start' => $currentPeriodStart,
            'current_period_end' => $currentPeriodEnd,
            'trial_ends_at' => $currentPeriodEnd,
            'created_by' => $user->id,
        ]);

        // Update transaction with subscription reference
        $transaction->update([
            'user_subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Admin: Get all transactions
     */
    public function adminIndex(Request $request): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $query = Transaction::with(['user', 'package', 'subscription'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('user_id')) {
            $userIds = explode(',', $request->user_id);
            $query->whereIn('user_id', $userIds);
        }

        if ($request->filled('date_range')) {
            $dateRange = $request->date_range;
            $now = \Carbon\Carbon::now();
            
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
        $perPage = $request->get('per_page', 15);
        $transactions = $query->paginate($perPage);

        // Create a clone of the query for summary calculations
        $summaryQuery = clone $query;
        $summaryQuery->getQuery()->orders = null; // Remove ordering for summary
        $summaryQuery->getQuery()->limit = null; // Remove limit for summary
        $summaryQuery->getQuery()->offset = null; // Remove offset for summary

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'summary' => [
                'total_transactions' => $summaryQuery->count(),
                'total_amount' => $summaryQuery->where('status', 'completed')->sum('amount'),
                'pending_transactions' => $summaryQuery->where('status', 'pending')->count(),
                'failed_transactions' => $summaryQuery->where('status', 'failed')->count(),
            ]
        ]);
    }

    /**
     * Admin: Get transaction statistics
     */
    public function adminStats(Request $request): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $stats = [
            'total_transactions' => Transaction::count(),
            'total_amount' => Transaction::completed()->sum('amount'),
            'pending_transactions' => Transaction::pending()->count(),
            'completed_transactions' => Transaction::completed()->count(),
            'failed_transactions' => Transaction::failed()->count(),
            'refunded_transactions' => Transaction::refunded()->count(),
            'cancelled_transactions' => Transaction::cancelled()->count(),
            'transactions_by_type' => [
                'subscription' => Transaction::byType(Transaction::TYPE_SUBSCRIPTION)->count(),
                'upgrade' => Transaction::byType(Transaction::TYPE_UPGRADE)->count(),
                'renewal' => Transaction::byType(Transaction::TYPE_RENEWAL)->count(),
                'refund' => Transaction::byType(Transaction::TYPE_REFUND)->count(),
                'trial' => Transaction::byType(Transaction::TYPE_TRIAL)->count(),
            ],
            'transactions_by_payment_method' => [
                'stripe' => Transaction::byPaymentMethod(Transaction::PAYMENT_STRIPE)->count(),
                'paypal' => Transaction::byPaymentMethod(Transaction::PAYMENT_PAYPAL)->count(),
                'manual' => Transaction::byPaymentMethod(Transaction::PAYMENT_MANUAL)->count(),
            ],
            'recent_transactions' => Transaction::with(['user', 'package'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
