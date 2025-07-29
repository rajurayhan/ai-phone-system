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
use Stripe\Product;
use Stripe\PaymentMethod;
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

            // If payment method is provided, validate and attach it to the customer first
            if ($paymentMethodId) {
                $this->validateAndAttachPaymentMethod($customerId, $paymentMethodId);
            }

            // Create subscription parameters
            $subscriptionData = [
                'customer' => $customerId,
                'items' => [
                    [
                        'price_data' => [
                            'currency' => config('stripe.currency'),
                            'product' => $this->getOrCreateProduct($package),
                            'unit_amount' => (int) ($package->price * 100), // Convert to cents
                            'recurring' => [
                                'interval' => 'month',
                            ],
                        ],
                    ],
                ],
                'metadata' => [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                ],
            ];

            // Add payment method if provided
            if ($paymentMethodId) {
                $subscriptionData['default_payment_method'] = $paymentMethodId;
            } else {
                // Only use payment_behavior when no payment method is provided
                $subscriptionData['payment_behavior'] = 'default_incomplete';
                $subscriptionData['payment_settings'] = [
                    'payment_method_types' => ['card'],
                    'save_default_payment_method' => 'on_subscription',
                ];
                $subscriptionData['expand'] = ['latest_invoice.payment_intent'];
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
            Log::error('Stripe subscription creation failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'payment_method_id' => $paymentMethodId,
                'error_code' => $e->getStripeCode(),
                'error_type' => $e->getStripeCode(),
            ]);
            
            // Provide more specific error messages
            $errorMessage = 'Failed to create subscription';
            if (str_contains($e->getMessage(), 'No such PaymentMethod')) {
                $errorMessage = 'The payment method is invalid or has expired. Please try again with a different payment method.';
            } elseif (str_contains($e->getMessage(), 'card was declined')) {
                $errorMessage = 'Your card was declined. Please try a different payment method.';
            } elseif (str_contains($e->getMessage(), 'insufficient funds')) {
                $errorMessage = 'Insufficient funds. Please try a different payment method.';
            }
            
            throw new \Exception($errorMessage);
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
     * Get or create a Stripe product
     */
    private function getOrCreateProduct(SubscriptionPackage $package): string
    {
        try {
            // Check if product already exists
            $existingProducts = \Stripe\Product::all(['limit' => 1, 'active' => true]);
            foreach ($existingProducts->data as $product) {
                if ($product->name === $package->name) {
                    return $product->id;
                }
            }

            // Create new product
            $product = \Stripe\Product::create([
                'name' => $package->name,
                'description' => $package->description,
                'metadata' => [
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                ],
            ]);

            return $product->id;
        } catch (ApiErrorException $e) {
            Log::error('Stripe product creation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Attach a payment method to a customer
     */
    private function attachPaymentMethodToCustomer(string $customerId, string $paymentMethodId): void
    {
        try {
            // Attach the payment method to the customer
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $customerId]);
            
            Log::info('Payment method attached to customer', [
                'customer_id' => $customerId,
                'payment_method_id' => $paymentMethodId
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe payment method attachment failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate and attach a payment method to a customer
     */
    private function validateAndAttachPaymentMethod(string $customerId, string $paymentMethodId): void
    {
        try {
            // First, validate the payment method exists
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            
            // Check if payment method is already attached to this customer
            if ($paymentMethod->customer && $paymentMethod->customer !== $customerId) {
                throw new \Exception('Payment method is already attached to a different customer');
            }
            
            // Attach the payment method to the customer if not already attached
            if (!$paymentMethod->customer) {
                $paymentMethod->attach(['customer' => $customerId]);
            }
            
            Log::info('Payment method validated and attached to customer', [
                'customer_id' => $customerId,
                'payment_method_id' => $paymentMethodId,
                'payment_method_type' => $paymentMethod->type
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe payment method validation/attachment failed: ' . $e->getMessage(), [
                'customer_id' => $customerId,
                'payment_method_id' => $paymentMethodId,
                'error_code' => $e->getStripeCode(),
                'error_type' => $e->getStripeCode(),
            ]);
            
            // Provide specific error messages based on the error
            if (str_contains($e->getMessage(), 'No such PaymentMethod')) {
                throw new \Exception('The payment method is invalid or has expired. Please try again with a different payment method.');
            } elseif (str_contains($e->getMessage(), 'already attached')) {
                throw new \Exception('This payment method is already in use. Please try a different payment method.');
            } else {
                throw new \Exception('Failed to validate payment method: ' . $e->getMessage());
            }
        }
    }

    /**
     * Clean up invalid PaymentMethod references in transactions
     */
    public function cleanupInvalidPaymentMethods(): array
    {
        $results = [
            'checked' => 0,
            'cleaned' => 0,
            'errors' => []
        ];
        
        try {
            // Get all transactions with payment_method_id
            $transactions = \App\Models\Transaction::whereNotNull('payment_method_id')->get();
            
            foreach ($transactions as $transaction) {
                $results['checked']++;
                
                try {
                    // Try to retrieve the PaymentMethod from Stripe
                    $paymentMethod = PaymentMethod::retrieve($transaction->payment_method_id);
                    
                    // If successful, the PaymentMethod exists
                    Log::info('PaymentMethod validation successful', [
                        'transaction_id' => $transaction->id,
                        'payment_method_id' => $transaction->payment_method_id
                    ]);
                    
                } catch (ApiErrorException $e) {
                    // PaymentMethod doesn't exist, clean it up
                    if (str_contains($e->getMessage(), 'No such PaymentMethod')) {
                        $transaction->update([
                            'payment_method_id' => null,
                            'payment_details' => array_merge($transaction->payment_details ?? [], [
                                'cleaned_up' => true,
                                'cleanup_reason' => 'PaymentMethod not found in Stripe',
                                'cleanup_date' => now()->toISOString()
                            ])
                        ]);
                        
                        $results['cleaned']++;
                        Log::info('Cleaned up invalid PaymentMethod reference', [
                            'transaction_id' => $transaction->id,
                            'payment_method_id' => $transaction->payment_method_id
                        ]);
                    } else {
                        $results['errors'][] = [
                            'transaction_id' => $transaction->id,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }
            
            Log::info('PaymentMethod cleanup completed', $results);
            return $results;
            
        } catch (\Exception $e) {
            Log::error('PaymentMethod cleanup failed: ' . $e->getMessage());
            $results['errors'][] = ['error' => $e->getMessage()];
            return $results;
        }
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
        } else {
            // Create local subscription if it doesn't exist
            $packageId = $subscriptionData['metadata']['package_id'] ?? null;
            if ($packageId) {
                UserSubscription::create([
                    'user_id' => $userId,
                    'subscription_package_id' => $packageId,
                    'status' => $subscriptionData['status'],
                    'current_period_start' => Carbon::createFromTimestamp($subscriptionData['current_period_start']),
                    'current_period_end' => Carbon::createFromTimestamp($subscriptionData['current_period_end']),
                    'trial_ends_at' => $subscriptionData['trial_end'] ? Carbon::createFromTimestamp($subscriptionData['trial_end']) : null,
                    'stripe_subscription_id' => $subscriptionData['id'],
                    'stripe_customer_id' => $subscriptionData['customer'],
                ]);
            }
        }

        Log::info('Subscription created/updated via webhook', [
            'subscription_id' => $subscriptionData['id'],
            'user_id' => $userId,
            'status' => $subscriptionData['status'],
            'period_start' => Carbon::createFromTimestamp($subscriptionData['current_period_start']),
            'period_end' => Carbon::createFromTimestamp($subscriptionData['current_period_end'])
        ]);
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
            // Get the actual subscription data from Stripe to get correct period dates
            try {
                $stripeSubscription = Subscription::retrieve($subscriptionId);
                
                // Activate the subscription with correct period dates
                $localSubscription->update([
                    'status' => 'active',
                    'current_period_start' => Carbon::createFromTimestamp($stripeSubscription->current_period_start),
                    'current_period_end' => Carbon::createFromTimestamp($stripeSubscription->current_period_end),
                    'trial_ends_at' => $stripeSubscription->trial_end ? Carbon::createFromTimestamp($stripeSubscription->trial_end) : null,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to retrieve Stripe subscription for period dates', [
                    'subscription_id' => $subscriptionId,
                    'error' => $e->getMessage()
                ]);
                
                // Fallback to invoice data if Stripe subscription retrieval fails
                $localSubscription->update([
                    'status' => 'active',
                    'current_period_start' => Carbon::createFromTimestamp($invoiceData['period_start'] ?? time()),
                    'current_period_end' => Carbon::createFromTimestamp($invoiceData['period_end'] ?? time() + 30 * 24 * 60 * 60),
                ]);
            }

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

                // Send invoice email to user
                try {
                    $user = $localSubscription->user;
                    $user->notify(new \App\Notifications\SubscriptionInvoice($localSubscription, $transaction, $invoiceData));
                    
                    Log::info('Invoice email sent successfully', [
                        'user_id' => $user->id,
                        'subscription_id' => $localSubscription->id,
                        'transaction_id' => $transaction->id,
                        'invoice_id' => $invoiceData['id']
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send invoice email', [
                        'user_id' => $localSubscription->user_id,
                        'subscription_id' => $localSubscription->id,
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Subscription activated via webhook', [
                'subscription_id' => $subscriptionId,
                'user_id' => $localSubscription->user_id,
                'invoice_id' => $invoiceData['id'],
                'period_start' => $localSubscription->current_period_start,
                'period_end' => $localSubscription->current_period_end
            ]);
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