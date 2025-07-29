<?php

namespace App\Services;

use App\Models\CallLog;
use App\Models\Assistant;
use Illuminate\Support\Facades\Log;

class VapiCallReportProcessor
{
    /**
     * Process end-of-call-report webhook data
     */
    public function processEndCallReport(array $webhookData): ?CallLog
    {
        try {
            $message = $webhookData['message'] ?? null;
            if (!$message) {
                Log::warning('Missing message in webhook data');
                return null;
            }

            // Extract call information
            $callId = $message['call']['id'] ?? null;
            $assistantId = $message['call']['assistantId'] ?? null;
            $timestamp = $message['timestamp'] ?? null;
            $startedAt = $message['startedAt'] ?? null;
            $endedAt = $message['endedAt'] ?? null;
            $endedReason = $message['endedReason'] ?? null;
            $durationMs = $message['durationMs'] ?? null;
            $durationSeconds = $message['durationSeconds'] ?? null;
            $cost = $message['cost'] ?? null;
            $costBreakdown = $message['costBreakdown'] ?? [];
            $summary = $message['analysis']['summary'] ?? null;
            $successEvaluation = $message['analysis']['successEvaluation'] ?? null;
            $transcript = $message['transcript'] ?? null;
            $messages = $message['artifact']['messages'] ?? [];
            $recordingUrl = $message['recordingUrl'] ?? null;
            $stereoRecordingUrl = $message['stereoRecordingUrl'] ?? null;

            // Extract phone numbers
            $phoneNumber = $message['phoneNumber']['number'] ?? null;
            $callerNumber = $message['customer']['number'] ?? null;

            // Find the assistant
            $assistant = Assistant::where('vapi_assistant_id', $assistantId)->first();
            if (!$assistant) {
                Log::warning('Assistant not found for vapi_assistant_id', [
                    'assistant_id' => $assistantId,
                    'call_id' => $callId
                ]);
                return null;
            }

            // Determine call status based on ended reason
            $status = $this->mapEndedReasonToStatus($endedReason);

            // Create or update call log
            $callLog = CallLog::updateOrCreate(
                ['call_id' => $callId],
                [
                    'assistant_id' => $assistant->id,
                    'user_id' => $assistant->user_id,
                    'phone_number' => $phoneNumber,
                    'caller_number' => $callerNumber,
                    'duration' => $durationSeconds,
                    'status' => $status,
                    'direction' => 'inbound', // Most Vapi calls are inbound
                    'start_time' => $startedAt ? \Carbon\Carbon::parse($startedAt) : null,
                    'end_time' => $endedAt ? \Carbon\Carbon::parse($endedAt) : null,
                    'transcript' => $transcript,
                    'summary' => $summary,
                    'cost' => $cost,
                    'currency' => 'USD',
                    'metadata' => [
                        'ended_reason' => $endedReason,
                        'duration_ms' => $durationMs,
                        'success_evaluation' => $successEvaluation,
                        'recording_url' => $recordingUrl,
                        'stereo_recording_url' => $stereoRecordingUrl,
                        'cost_breakdown' => $costBreakdown,
                        'messages_count' => count($messages),
                        'timestamp' => $timestamp,
                    ],
                    'webhook_data' => $webhookData,
                ]
            );

            Log::info('Call log processed from end-of-call-report', [
                'call_id' => $callId,
                'assistant_id' => $assistantId,
                'status' => $status,
                'duration' => $durationSeconds,
                'cost' => $cost
            ]);

            return $callLog;

        } catch (\Exception $e) {
            Log::error('Error processing end-of-call-report webhook', [
                'error' => $e->getMessage(),
                'webhook_data' => $webhookData
            ]);
            return null;
        }
    }

    /**
     * Map Vapi ended reason to internal status
     */
    private function mapEndedReasonToStatus(?string $endedReason): string
    {
        if (!$endedReason) {
            return 'completed';
        }

        $statusMap = [
            'customer-ended-call' => 'completed',
            'assistant-ended-call' => 'completed',
            'call-failed' => 'failed',
            'no-answer' => 'no-answer',
            'busy' => 'busy',
            'cancelled' => 'cancelled',
            'timeout' => 'failed',
            'error' => 'failed',
        ];

        return $statusMap[$endedReason] ?? 'completed';
    }

    /**
     * Extract contact information from transcript
     */
    public function extractContactInfo(array $webhookData): array
    {
        $contactInfo = [
            'name' => null,
            'email' => null,
            'phone' => null,
            'company' => null,
            'inquiry_type' => null,
        ];

        try {
            $summary = $webhookData['message']['analysis']['summary'] ?? '';
            $transcript = $webhookData['message']['transcript'] ?? '';
            $messages = $webhookData['message']['artifact']['messages'] ?? [];

            // Extract from summary
            if (preg_match('/([A-Z][a-z]+ [A-Z][a-z]+) called/', $summary, $matches)) {
                $contactInfo['name'] = $matches[1];
            }

            if (preg_match('/phone number \(([^)]+)\)/', $summary, $matches)) {
                $contactInfo['phone'] = $matches[1];
            }

            if (preg_match('/email address \(([^)]+)\)/', $summary, $matches)) {
                $contactInfo['email'] = $matches[1];
            }

            // Extract from transcript
            if (!$contactInfo['name'] && preg_match('/name is ([A-Z][a-z]+ [A-Z][a-z]+)/', $transcript, $matches)) {
                $contactInfo['name'] = $matches[1];
            }

            // Extract inquiry type
            if (preg_match('/interested in ([^.]+)/', $summary, $matches)) {
                $contactInfo['inquiry_type'] = trim($matches[1]);
            }

            // Extract from messages
            foreach ($messages as $message) {
                if ($message['role'] === 'user') {
                    $content = $message['message'] ?? '';
                    
                    // Look for email patterns
                    if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $content, $matches)) {
                        $contactInfo['email'] = $matches[0];
                    }
                    
                    // Look for phone patterns
                    if (preg_match('/\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/', $content, $matches)) {
                        $contactInfo['phone'] = $matches[0];
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Error extracting contact info', [
                'error' => $e->getMessage()
            ]);
        }

        return $contactInfo;
    }

    /**
     * Get call quality metrics
     */
    public function getCallQualityMetrics(array $webhookData): array
    {
        $metrics = [
            'success' => false,
            'duration_seconds' => 0,
            'messages_count' => 0,
            'cost_usd' => 0,
            'has_transcript' => false,
            'has_summary' => false,
            'has_recording' => false,
        ];

        try {
            $message = $webhookData['message'] ?? [];
            
            $metrics['success'] = $message['analysis']['successEvaluation'] ?? false;
            $metrics['duration_seconds'] = $message['durationSeconds'] ?? 0;
            $metrics['messages_count'] = count($message['artifact']['messages'] ?? []);
            $metrics['cost_usd'] = $message['cost'] ?? 0;
            $metrics['has_transcript'] = !empty($message['transcript']);
            $metrics['has_summary'] = !empty($message['analysis']['summary']);
            $metrics['has_recording'] = !empty($message['recordingUrl']);

        } catch (\Exception $e) {
            Log::error('Error getting call quality metrics', [
                'error' => $e->getMessage()
            ]);
        }

        return $metrics;
    }
} 