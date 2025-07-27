<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Transaction;
use App\Models\SubscriptionPackage;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;
use Carbon\Carbon;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('stripe.secret_key'));
        Stripe::setApiVersion(config('stripe.api_version'));
    }

    /**
     * Create a Stripe customer
     */
    public function createCustomer(User $user): ?string
    {
        try {
            $customer = Customer::create([
                'email' => $user->email,
                'name' => $user->name,
                'metadata' => [
                    'user_id' => $user->id,
                    'created_at' => $user->created_at->toISOString(),
                ],
            ]);

            return $customer->id;
        } catch (ApiErrorException $e) {
            Log::error('Stripe customer creation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a Stripe subscription
     */
    public function createSubscription(User $user, SubscriptionPackage $package, string $paymentMethodId = null): ?array
    {
        try {
            // Get or create Stripe customer
            $customerId = $this->getOrCreateCustomer($user);
            if (!$customerId) {
                throw new \Exception('Failed to create or retrieve customer');
            }

            // Create subscription parameters
            $subscriptionData = [
                'customer' => $customerId,
                'items' => [
                    [
                        'price_data' => [
                            'currency' => config('stripe.currency'),
                            'product_data' => [
                                'name' => $package->name,
                                'description' => $package->description,
                            ],
                            'unit_amount' => (int) ($package->price * 100), // Convert to cents
                            'recurring' => [
                                'interval' => 'month',
                            ],
                        ],
                    ],
                ],
                'trial_period_days' => config('stripe.subscription.trial_period_days'),
                'metadata' => [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                ],
            ];

            // Add payment method if provided
            if ($paymentMethodId) {
                $subscriptionData['default_payment_method'] = $paymentMethodId;
            }

            $subscription = Subscription::create($subscriptionData);

            return [
                'subscription_id' => $subscription->id,
                'customer_id' => $customerId,
                'status' => $subscription->status,
                'current_period_start' => Carbon::createFromTimestamp($subscription->current_period_start),
                'current_period_end' => Carbon::createFromTimestamp($subscription->current_period_end),
                'trial_end' => $subscription->trial_end ? Carbon::createFromTimestamp($subscription->trial_end) : null,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe subscription creation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a payment intent for one-time payments
     */
    public function createPaymentIntent(User $user, float $amount, string $currency = 'usd', array $metadata = []): ?array
    {
        try {
            $customerId = $this->getOrCreateCustomer($user);

            $paymentIntent = PaymentIntent::create([
                'amount' => (int) ($amount * 100), // Convert to cents
                'currency' => $currency,
                'customer' => $customerId,
                'metadata' => array_merge($metadata, [
                    'user_id' => $user->id,
                ]),
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return [
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'amount' => $amount,
                'currency' => $currency,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe payment intent creation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cancel a Stripe subscription
     */
    public function cancelSubscription(string $stripeSubscriptionId, bool $atPeriodEnd = true): bool
    {
        try {
            $subscription = Subscription::retrieve($stripeSubscriptionId);
            
            if ($atPeriodEnd) {
                $subscription->cancel_at_period_end = true;
            } else {
                $subscription->cancel();
            }
            
            $subscription->save();
            return true;
        } catch (ApiErrorException $e) {
            Log::error('Stripe subscription cancellation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update subscription (upgrade/downgrade)
     */
    public function updateSubscription(string $stripeSubscriptionId, SubscriptionPackage $newPackage): bool
    {
        try {
            $subscription = Subscription::retrieve($stripeSubscriptionId);
            
            // Update the subscription item with new price
            $subscription->items->data[0]->price_data = [
                'currency' => config('stripe.currency'),
                'product_data' => [
                    'name' => $newPackage->name,
                    'description' => $newPackage->description,
                ],
                'unit_amount' => (int) ($newPackage->price * 100),
                'recurring' => [
                    'interval' => 'month',
                ],
            ];
            
            $subscription->save();
            return true;
        } catch (ApiErrorException $e) {
            Log::error('Stripe subscription update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve a Stripe subscription
     */
    public function getSubscription(string $stripeSubscriptionId): ?array
    {
        try {
            $subscription = Subscription::retrieve($stripeSubscriptionId);
            
            return [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'current_period_start' => Carbon::createFromTimestamp($subscription->current_period_start),
                'current_period_end' => Carbon::createFromTimestamp($subscription->current_period_end),
                'trial_end' => $subscription->trial_end ? Carbon::createFromTimestamp($subscription->trial_end) : null,
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe subscription retrieval failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get or create a Stripe customer for a user
     */
    private function getOrCreateCustomer(User $user): ?string
    {
        // Check if user already has a Stripe customer ID
        $existingSubscription = $user->subscriptions()->whereNotNull('stripe_customer_id')->first();
        if ($existingSubscription && $existingSubscription->stripe_customer_id) {
            return $existingSubscription->stripe_customer_id;
        }

        // Create new customer
        return $this->createCustomer($user);
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        try {
            $event = \Stripe\Webhook::constructEvent($payload, $signature, $secret);
            return true;
        } catch (\Exception $e) {
            Log::error('Webhook signature verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process webhook event
     */
    public function processWebhookEvent(array $event): void
    {
        $eventType = $event['type'];
        
        switch ($eventType) {
            case 'customer.subscription.created':
                $this->handleSubscriptionCreated($event['data']['object']);
                break;
            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event['data']['object']);
                break;
            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event['data']['object']);
                break;
            case 'invoice.payment_succeeded':
                $this->handlePaymentSucceeded($event['data']['object']);
                break;
            case 'invoice.payment_failed':
                $this->handlePaymentFailed($event['data']['object']);
                break;
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event['data']['object']);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event['data']['object']);
                break;
        }
    }

    /**
     * Handle subscription created webhook
     */
    private function handleSubscriptionCreated(array $subscriptionData): void
    {
        $userId = $subscriptionData['metadata']['user_id'] ?? null;
        if (!$userId) return;

        $user = User::find($userId);
        if (!$user) return;

        // Update local subscription
        $localSubscription = $user->subscriptions()->where('stripe_subscription_id', $subscriptionData['id'])->first();
        if ($localSubscription) {
            $localSubscription->update([
                'status' => $subscriptionData['status'],
                'current_period_start' => Carbon::createFromTimestamp($subscriptionData['current_period_start']),
                'current_period_end' => Carbon::createFromTimestamp($subscriptionData['current_period_end']),
                'trial_ends_at' => $subscriptionData['trial_end'] ? Carbon::createFromTimestamp($subscriptionData['trial_end']) : null,
            ]);
        }
    }

    /**
     * Handle subscription updated webhook
     */
    private function handleSubscriptionUpdated(array $subscriptionData): void
    {
        $this->handleSubscriptionCreated($subscriptionData);
    }

    /**
     * Handle subscription deleted webhook
     */
    private function handleSubscriptionDeleted(array $subscriptionData): void
    {
        $userId = $subscriptionData['metadata']['user_id'] ?? null;
        if (!$userId) return;

        $user = User::find($userId);
        if (!$user) return;

        // Update local subscription
        $localSubscription = $user->subscriptions()->where('stripe_subscription_id', $subscriptionData['id'])->first();
        if ($localSubscription) {
            $localSubscription->update([
                'status' => 'cancelled',
                'cancelled_at' => Carbon::now(),
                'ends_at' => Carbon::createFromTimestamp($subscriptionData['current_period_end']),
            ]);
        }
    }

    /**
     * Handle payment succeeded webhook
     */
    private function handlePaymentSucceeded(array $invoiceData): void
    {
        $subscriptionId = $invoiceData['subscription'] ?? null;
        if (!$subscriptionId) return;

        $localSubscription = UserSubscription::where('stripe_subscription_id', $subscriptionId)->first();
        if ($localSubscription) {
            // Update transaction status
            $transaction = Transaction::where('user_subscription_id', $localSubscription->id)
                ->where('status', Transaction::STATUS_PENDING)
                ->latest()
                ->first();

            if ($transaction) {
                $transaction->update([
                    'status' => Transaction::STATUS_COMPLETED,
                    'external_transaction_id' => $invoiceData['id'],
                    'processed_at' => Carbon::now(),
                ]);
            }
        }
    }

    /**
     * Handle payment failed webhook
     */
    private function handlePaymentFailed(array $invoiceData): void
    {
        $subscriptionId = $invoiceData['subscription'] ?? null;
        if (!$subscriptionId) return;

        $localSubscription = UserSubscription::where('stripe_subscription_id', $subscriptionId)->first();
        if ($localSubscription) {
            // Update transaction status
            $transaction = Transaction::where('user_subscription_id', $localSubscription->id)
                ->where('status', Transaction::STATUS_PENDING)
                ->latest()
                ->first();

            if ($transaction) {
                $transaction->update([
                    'status' => Transaction::STATUS_FAILED,
                    'failed_at' => Carbon::now(),
                ]);
            }
        }
    }

    /**
     * Handle payment intent succeeded webhook
     */
    private function handlePaymentIntentSucceeded(array $paymentIntentData): void
    {
        $transaction = Transaction::where('external_transaction_id', $paymentIntentData['id'])->first();
        if ($transaction) {
            $transaction->update([
                'status' => Transaction::STATUS_COMPLETED,
                'processed_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Handle payment intent failed webhook
     */
    private function handlePaymentIntentFailed(array $paymentIntentData): void
    {
        $transaction = Transaction::where('external_transaction_id', $paymentIntentData['id'])->first();
        if ($transaction) {
            $transaction->update([
                'status' => Transaction::STATUS_FAILED,
                'failed_at' => Carbon::now(),
            ]);
        }
    }
} 