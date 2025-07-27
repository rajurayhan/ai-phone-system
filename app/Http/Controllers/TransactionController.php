<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\UserSubscription;
use App\Models\SubscriptionPackage;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TransactionController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Get user's transactions
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = Transaction::where('user_id', $user->id)
            ->with(['package', 'subscription'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $transactions = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Get specific transaction
     */
    public function show(Request $request, $transactionId): JsonResponse
    {
        $user = Auth::user();
        
        $transaction = Transaction::where('user_id', $user->id)
            ->where('transaction_id', $transactionId)
            ->with(['package', 'subscription'])
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
        
        $request->validate([
            'status' => 'required|in:pending,completed,failed,refunded,cancelled'
        ]);

        $transaction = Transaction::where('user_id', $user->id)
            ->where('transaction_id', $transactionId)
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        $transaction->update([
            'status' => $request->status,
            'processed_at' => $request->status === Transaction::STATUS_COMPLETED ? Carbon::now() : null,
            'failed_at' => $request->status === Transaction::STATUS_FAILED ? Carbon::now() : null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $transaction->load(['package', 'subscription']),
            'message' => 'Transaction status updated successfully'
        ]);
    }

    /**
     * Process payment with Stripe integration
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

        try {
            if ($transaction->payment_method === Transaction::PAYMENT_STRIPE) {
                // Process with Stripe
                return $this->processStripePayment($transaction, $user);
            } else {
                // For other payment methods, simulate processing
                return $this->processMockPayment($transaction, $user);
            }
        } catch (\Exception $e) {
            $transaction->update([
                'status' => Transaction::STATUS_FAILED,
                'failed_at' => Carbon::now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Process payment with Stripe
     */
    private function processStripePayment(Transaction $transaction, $user): JsonResponse
    {
        $package = $transaction->package;

        // Create Stripe subscription
        $stripeResult = $this->stripeService->createSubscription($user, $package);

        if (!$stripeResult) {
            $transaction->update([
                'status' => Transaction::STATUS_FAILED,
                'failed_at' => Carbon::now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create Stripe subscription'
            ], 400);
        }

        // Update transaction with Stripe data
        $transaction->update([
            'status' => Transaction::STATUS_COMPLETED,
            'external_transaction_id' => $stripeResult['subscription_id'],
            'processed_at' => Carbon::now(),
        ]);

        // Update or create local subscription
        $this->createOrUpdateSubscriptionWithStripe($user, $transaction, $stripeResult);

        return response()->json([
            'success' => true,
            'data' => $transaction->load(['package', 'subscription']),
            'message' => 'Payment processed successfully and subscription activated!'
        ]);
    }

    /**
     * Process mock payment for non-Stripe methods
     */
    private function processMockPayment(Transaction $transaction, $user): JsonResponse
    {
        // Simulate payment processing
        $success = rand(1, 10) > 2; // 80% success rate for demo

        if ($success) {
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
     * Create or update subscription with Stripe data
     */
    private function createOrUpdateSubscriptionWithStripe($user, $transaction, $stripeResult): void
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

        // Create new subscription with Stripe data
        $package = $transaction->package;
        
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_package_id' => $package->id,
            'status' => $stripeResult['status'],
            'current_period_start' => $stripeResult['current_period_start'],
            'current_period_end' => $stripeResult['current_period_end'],
            'stripe_subscription_id' => $stripeResult['subscription_id'],
            'stripe_customer_id' => $stripeResult['customer_id'],
            'created_by' => $user->id,
        ]);

        // Update transaction with subscription reference
        $transaction->update([
            'user_subscription_id' => $subscription->id,
        ]);
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
        $currentPeriodStart = Carbon::now();
        $currentPeriodEnd = Carbon::now()->addMonth();

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_package_id' => $package->id,
            'status' => 'active',
            'current_period_start' => $currentPeriodStart,
            'current_period_end' => $currentPeriodEnd,
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
        $currentPeriodStart = Carbon::now();
        $currentPeriodEnd = Carbon::now()->addMonth();

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_package_id' => $package->id,
            'status' => 'pending', // Set status to pending
            'current_period_start' => $currentPeriodStart,
            'current_period_end' => $currentPeriodEnd,
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
        $query = Transaction::with(['user', 'package', 'subscription'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $transactions = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Admin: Get transaction statistics
     */
    public function adminStats(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', Carbon::now()->subDays(30));
        $endDate = $request->get('end_date', Carbon::now());

        $stats = [
            'total_transactions' => Transaction::whereBetween('created_at', [$startDate, $endDate])->count(),
            'total_amount' => Transaction::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', Transaction::STATUS_COMPLETED)
                ->sum('amount'),
            'completed_transactions' => Transaction::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', Transaction::STATUS_COMPLETED)
                ->count(),
            'failed_transactions' => Transaction::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', Transaction::STATUS_FAILED)
                ->count(),
            'pending_transactions' => Transaction::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', Transaction::STATUS_PENDING)
                ->count(),
            'payment_methods' => [
                'stripe' => Transaction::byPaymentMethod(Transaction::PAYMENT_STRIPE)->count(),
                'paypal' => Transaction::byPaymentMethod(Transaction::PAYMENT_PAYPAL)->count(),
                'manual' => Transaction::byPaymentMethod(Transaction::PAYMENT_MANUAL)->count(),
            ],
            'transaction_types' => [
                'subscription' => Transaction::byType(Transaction::TYPE_SUBSCRIPTION)->count(),
                'upgrade' => Transaction::byType(Transaction::TYPE_UPGRADE)->count(),
                'renewal' => Transaction::byType(Transaction::TYPE_RENEWAL)->count(),
                'refund' => Transaction::byType(Transaction::TYPE_REFUND)->count(),
                'trial' => Transaction::byType(Transaction::TYPE_TRIAL)->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
