<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Assistant;
use App\Models\CallLog;
use App\Models\User;
use Carbon\Carbon;

class SyncVapiCalls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vapi:sync-calls 
                            {--assistant-id= : Sync calls for specific assistant ID}
                            {--limit=100 : Number of calls to fetch per assistant}
                            {--dry-run : Show what would be synced without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync calls from Vapi API with local call logs database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Vapi calls sync...');

        // Get Vapi API token from environment
        $vapiToken = config('services.vapi.token');
        if (!$vapiToken) {
            $this->error('VAPI_TOKEN not found in environment variables');
            return 1;
        }

        $assistantId = $this->option('assistant-id');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Get assistants to sync
        $assistants = $this->getAssistants($assistantId);
        
        if ($assistants->isEmpty()) {
            $this->error('No assistants found to sync');
            return 1;
        }

        $this->info("Found {$assistants->count()} assistant(s) to sync");

        $totalSynced = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        foreach ($assistants as $assistant) {
            $this->info("\nSyncing calls for assistant: {$assistant->name} (ID: {$assistant->vapi_assistant_id})");
            
            try {
                $result = $this->syncAssistantCalls($assistant, $limit, $dryRun);
                $totalSynced += $result['synced'];
                $totalSkipped += $result['skipped'];
                $totalErrors += $result['errors'];
                
                $this->info("Assistant {$assistant->name}: {$result['synced']} synced, {$result['skipped']} skipped, {$result['errors']} errors");
            } catch (\Exception $e) {
                $this->error("Error syncing assistant {$assistant->name}: " . $e->getMessage());
                $totalErrors++;
            }
        }

        $this->info("\n" . str_repeat('=', 50));
        $this->info("SYNC SUMMARY:");
        $this->info("Total Synced: {$totalSynced}");
        $this->info("Total Skipped: {$totalSkipped}");
        $this->info("Total Errors: {$totalErrors}");
        
        if ($dryRun) {
            $this->warn('This was a dry run - no actual changes were made');
        }

        return 0;
    }

    /**
     * Get assistants to sync
     */
    private function getAssistants($assistantId = null)
    {
        $query = Assistant::whereNotNull('vapi_assistant_id');
        
        if ($assistantId) {
            $query->where('id', $assistantId);
        }

        return $query->get();
    }

    /**
     * Sync calls for a specific assistant
     */
    private function syncAssistantCalls(Assistant $assistant, int $limit, bool $dryRun)
    {
        $synced = 0;
        $skipped = 0;
        $errors = 0;

        try {
            // Fetch calls from Vapi API
            $calls = $this->fetchVapiCalls($assistant->vapi_assistant_id, $limit);
            
            if (empty($calls)) {
                $this->warn("No calls found for assistant {$assistant->name}");
                return ['synced' => 0, 'skipped' => 0, 'errors' => 0];
            }

            $this->info("Found " . count($calls) . " calls for assistant {$assistant->name}");

            foreach ($calls as $call) {
                try {
                    $result = $this->processCall($call, $assistant, $dryRun);
                    
                    if ($result === 'synced') {
                        $synced++;
                    } elseif ($result === 'skipped') {
                        $skipped++;
                    } else {
                        $errors++;
                    }
                } catch (\Exception $e) {
                    $this->error("Error processing call {$call['id']}: " . $e->getMessage());
                    $errors++;
                }
            }

        } catch (\Exception $e) {
            $this->error("Error fetching calls for assistant {$assistant->name}: " . $e->getMessage());
            $errors++;
        }

        return ['synced' => $synced, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Fetch calls from Vapi API
     */
    private function fetchVapiCalls(string $assistantId, int $limit)
    {
        $vapiToken = config('services.vapi.token');
        $baseUrl = config('services.vapi.base_url', 'https://api.vapi.ai');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$vapiToken}",
            'Content-Type' => 'application/json',
        ])->get("{$baseUrl}/call", [
            'assistantId' => $assistantId,
            'limit' => $limit,
        ]);

        if (!$response->successful()) {
            throw new \Exception("Vapi API error: " . $response->status() . " - " . $response->body());
        }

        $data = $response->json();
        
        if (!is_array($data)) {
            throw new \Exception("Invalid response format from Vapi API");
        }

        return $data;
    }

    /**
     * Process a single call
     */
    private function processCall(array $call, Assistant $assistant, bool $dryRun)
    {
        $callId = $call['id'];
        
        // Check if call already exists
        $existingCall = CallLog::where('call_id', $callId)->first();
        
        if ($existingCall) {
            if ($dryRun) {
                $this->line("Would update existing call: {$callId}");
            } else {
                $this->updateCallLog($existingCall, $call, $assistant);
                $this->line("Updated call: {$callId}");
            }
            return 'synced';
        }

        if ($dryRun) {
            $this->line("Would create new call: {$callId}");
        } else {
            $this->createCallLog($call, $assistant);
            $this->line("Created call: {$callId}");
        }
        
        return 'synced';
    }

    /**
     * Create a new call log entry
     */
    private function createCallLog(array $call, Assistant $assistant)
    {
        $callLog = new CallLog();
        $this->mapCallData($callLog, $call, $assistant);
        $callLog->save();

        Log::info('Created call log from Vapi sync', [
            'call_id' => $call['id'],
            'assistant_id' => $assistant->id,
            'user_id' => $assistant->user_id
        ]);
    }

    /**
     * Update existing call log entry
     */
    private function updateCallLog(CallLog $callLog, array $call, Assistant $assistant)
    {
        $this->mapCallData($callLog, $call, $assistant);
        $callLog->save();

        Log::info('Updated call log from Vapi sync', [
            'call_id' => $call['id'],
            'assistant_id' => $assistant->id,
            'user_id' => $assistant->user_id
        ]);
    }

    /**
     * Map Vapi call data to CallLog model
     */
    private function mapCallData(CallLog $callLog, array $call, Assistant $assistant)
    {
        // Basic call information
        $callLog->call_id = $call['id'];
        $callLog->assistant_id = $assistant->id;
        $callLog->user_id = $assistant->user_id;
        
        // Phone numbers
        $callLog->phone_number = $call['phoneNumber']['number'] ?? null;
        $callLog->caller_number = $call['customer']['number'] ?? null;
        
        // Timing
        $callLog->start_time = $call['startedAt'] ? Carbon::parse($call['startedAt']) : null;
        $callLog->end_time = $call['endedAt'] ? Carbon::parse($call['endedAt']) : null;
        
        // Calculate duration
        if ($callLog->start_time && $callLog->end_time) {
            $callLog->duration = $callLog->end_time->diffInSeconds($callLog->start_time);
        }
        
        // Status mapping
        $callLog->status = $this->mapVapiStatus($call['status']);
        $callLog->direction = $this->mapVapiDirection($call['type']);
        
        // Cost information
        $callLog->cost = $call['cost'] ?? null;
        $callLog->currency = 'USD'; // Vapi costs are in USD
        
        // Store full webhook data
        $callLog->webhook_data = $call;
        
        // Extract transcript if available
        if (isset($call['artifact']['transcript'])) {
            $callLog->transcript = $call['artifact']['transcript'];
        }
        
        // Extract summary if available
        if (isset($call['analysis']['summary'])) {
            $callLog->summary = $call['analysis']['summary'];
        }
        
        // Extract metadata
        $metadata = [];
        if (isset($call['assistant']['metadata'])) {
            $metadata['assistant_metadata'] = $call['assistant']['metadata'];
        }
        if (isset($call['endedReason'])) {
            $metadata['ended_reason'] = $call['endedReason'];
        }
        if (isset($call['costBreakdown'])) {
            $metadata['cost_breakdown'] = $call['costBreakdown'];
        }
        
        if (!empty($metadata)) {
            $callLog->metadata = $metadata;
        }
    }

    /**
     * Map Vapi status to our status enum
     */
    private function mapVapiStatus(?string $vapiStatus): string
    {
        if (!$vapiStatus) {
            return 'initiated';
        }

        $statusMap = [
            'scheduled' => 'initiated',
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

    /**
     * Map Vapi call type to our direction enum
     */
    private function mapVapiDirection(?string $vapiType): string
    {
        if (!$vapiType) {
            return 'inbound';
        }

        return str_contains($vapiType, 'outbound') ? 'outbound' : 'inbound';
    }
} 