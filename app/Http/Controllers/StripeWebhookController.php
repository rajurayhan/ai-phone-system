<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Handle Stripe webhook events
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('stripe.webhook_secret');

        // Verify webhook signature
        if (!$this->stripeService->verifyWebhookSignature($payload, $signature, $webhookSecret)) {
            Log::error('Invalid webhook signature');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        try {
            $event = json_decode($payload, true);
            
            // Process the webhook event
            $this->stripeService->processWebhookEvent($event);
            
            Log::info('Webhook processed successfully', ['event_type' => $event['type']]);
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Webhook processing failed: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }
} 