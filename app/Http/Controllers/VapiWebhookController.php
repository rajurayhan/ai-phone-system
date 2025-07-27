<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\CallLog;
use App\Models\Assistant;

class VapiWebhookController extends Controller
{
    /**
     * Handle incoming webhook events from Vapi.ai
     */
    public function handleWebhook(Request $request)
    {
        try {
            $payload = $request->all();
            Log::info('Vapi webhook received', $payload);

            $eventType = $payload['type'] ?? null;
            $callId = $payload['callId'] ?? null;
            $assistantId = $payload['assistantId'] ?? null;

            if (!$eventType || !$callId || !$assistantId) {
                Log::warning('Missing required fields in webhook payload', $payload);
                return response()->json(['success' => false, 'message' => 'Missing required fields'], 400);
            }

            // Find the assistant
            $assistant = Assistant::where('vapi_assistant_id', $assistantId)->first();
            if (!$assistant) {
                Log::warning('Assistant not found for vapi_assistant_id', ['assistant_id' => $assistantId]);
                return response()->json(['success' => false, 'message' => 'Assistant not found'], 404);
            }

            // Handle different event types
            switch ($eventType) {
                case 'call-start':
                    $this->handleCallStart($payload, $assistant);
                    break;
                case 'call-end':
                    $this->handleCallEnd($payload, $assistant);
                    break;
                case 'call-update':
                    $this->handleCallUpdate($payload, $assistant);
                    break;
                default:
                    Log::warning('Unknown event type', ['type' => $eventType]);
                    return response()->json(['success' => false, 'message' => 'Unknown event type'], 400);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Error processing webhook', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);
            return response()->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle call start event
     */
    private function handleCallStart(array $payload, Assistant $assistant)
    {
        $callLog = CallLog::updateOrCreate(
            ['call_id' => $payload['callId']],
            [
                'assistant_id' => $assistant->id,
                'user_id' => $assistant->user_id,
                'phone_number' => $payload['phoneNumber'] ?? null,
                'caller_number' => $payload['callerNumber'] ?? null,
                'status' => $this->mapVapiStatus($payload['status'] ?? 'initiated'),
                'direction' => $payload['direction'] ?? 'inbound',
                'start_time' => now(),
                'metadata' => $payload,
                'webhook_data' => $payload,
            ]
        );

        Log::info('Call log created/updated for call start', [
            'call_id' => $callLog->call_id,
            'assistant_id' => $assistant->id
        ]);
    }

    /**
     * Handle call end event
     */
    private function handleCallEnd(array $payload, Assistant $assistant)
    {
        $callLog = CallLog::where('call_id', $payload['callId'])->first();

        if (!$callLog) {
            // Create call log if it doesn't exist (in case we missed the start event)
            $callLog = CallLog::create([
                'call_id' => $payload['callId'],
                'assistant_id' => $assistant->id,
                'user_id' => $assistant->user_id,
                'phone_number' => $payload['phoneNumber'] ?? null,
                'caller_number' => $payload['callerNumber'] ?? null,
                'status' => $this->mapVapiStatus($payload['status'] ?? 'completed'),
                'direction' => $payload['direction'] ?? 'inbound',
                'start_time' => $payload['startTime'] ?? now(),
                'end_time' => now(),
                'duration' => $payload['duration'] ?? null,
                'transcript' => $payload['transcript'] ?? null,
                'summary' => $payload['summary'] ?? null,
                'cost' => $payload['cost'] ?? null,
                'currency' => $payload['currency'] ?? 'USD',
                'metadata' => $payload,
                'webhook_data' => $payload,
            ]);
        } else {
            // Update existing call log
            $callLog->update([
                'status' => $this->mapVapiStatus($payload['status'] ?? 'completed'),
                'end_time' => now(),
                'duration' => $payload['duration'] ?? $callLog->duration,
                'transcript' => $payload['transcript'] ?? $callLog->transcript,
                'summary' => $payload['summary'] ?? $callLog->summary,
                'cost' => $payload['cost'] ?? $callLog->cost,
                'currency' => $payload['currency'] ?? $callLog->currency,
                'webhook_data' => $payload,
            ]);
        }

        Log::info('Call log updated for call end', [
            'call_id' => $callLog->call_id,
            'status' => $callLog->status,
            'duration' => $callLog->duration
        ]);
    }

    /**
     * Handle call update event
     */
    private function handleCallUpdate(array $payload, Assistant $assistant)
    {
        $callLog = CallLog::where('call_id', $payload['callId'])->first();

        if ($callLog) {
            $callLog->update([
                'status' => $this->mapVapiStatus($payload['status'] ?? $callLog->status),
                'webhook_data' => $payload,
            ]);

            Log::info('Call log updated for call update', [
                'call_id' => $callLog->call_id,
                'status' => $callLog->status
            ]);
        }
    }

    /**
     * Map Vapi status to internal status
     */
    private function mapVapiStatus(string $vapiStatus): string
    {
        $statusMap = [
            'initiated' => 'initiated',
            'ringing' => 'ringing',
            'in-progress' => 'in-progress',
            'completed' => 'completed',
            'failed' => 'failed',
            'busy' => 'busy',
            'no-answer' => 'no-answer',
            'cancelled' => 'cancelled',
        ];

        return $statusMap[$vapiStatus] ?? 'initiated';
    }
} 