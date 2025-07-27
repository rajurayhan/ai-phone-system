<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for Stripe payment processing.
    |
    */

    'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Stripe API Version
    |--------------------------------------------------------------------------
    |
    | The Stripe API version to use for requests.
    |
    */
    'api_version' => env('STRIPE_API_VERSION', '2024-12-18'),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | The default currency for payments.
    |
    */
    'currency' => env('STRIPE_CURRENCY', 'usd'),

    /*
    |--------------------------------------------------------------------------
    | Payment Methods
    |--------------------------------------------------------------------------
    |
    | The payment methods to enable for Stripe.
    |
    */
    'payment_methods' => [
        'card',
        'bank_transfer',
        'sepa_debit',
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for subscription creation.
    |
    */
    'subscription' => [
        'trial_period_days' => env('STRIPE_TRIAL_PERIOD_DAYS', 14),
        'proration_behavior' => env('STRIPE_PRORATION_BEHAVIOR', 'create_prorations'),
        'collection_method' => env('STRIPE_COLLECTION_METHOD', 'charge_automatically'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Events
    |--------------------------------------------------------------------------
    |
    | The webhook events to handle.
    |
    */
    'webhook_events' => [
        'customer.subscription.created',
        'customer.subscription.updated',
        'customer.subscription.deleted',
        'invoice.payment_succeeded',
        'invoice.payment_failed',
        'payment_intent.succeeded',
        'payment_intent.payment_failed',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Whether to log Stripe API requests and responses.
    |
    */
    'logging' => env('STRIPE_LOGGING', true),

    /*
    |--------------------------------------------------------------------------
    | Test Mode
    |--------------------------------------------------------------------------
    |
    | Whether to use Stripe test mode.
    |
    */
    'test_mode' => env('STRIPE_TEST_MODE', true),
]; 