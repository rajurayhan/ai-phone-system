<?php

namespace App\Console\Commands;

use App\Models\Assistant;
use App\Models\User;
use App\Services\VapiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAssistants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assistants:sync 
                            {--user-id= : Sync assistants for a specific user ID}
                            {--assistant-id= : Sync a specific assistant by Vapi ID}
                            {--force : Force update existing assistants}
                            {--dry-run : Show what would be synced without making changes}
                            {--sync-missing : Also sync assistants that exist in Vapi but not in local database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize assistants data from Vapi.ai with local database';

    protected $vapiService;

    public function __construct(VapiService $vapiService)
    {
        parent::__construct();
        $this->vapiService = $vapiService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting assistant synchronization...');

        try {
            if ($this->option('assistant-id')) {
                $this->syncSpecificAssistant($this->option('assistant-id'));
            } elseif ($this->option('user-id')) {
                $this->syncUserAssistants($this->option('user-id'));
            } else {
                $this->syncAllAssistants();
            }

            $this->info('Assistant synchronization completed successfully!');
        } catch (\Exception $e) {
            $this->error('Synchronization failed: ' . $e->getMessage());
            Log::error('Assistant sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Sync a specific assistant by Vapi ID
     */
    protected function syncSpecificAssistant(string $assistantId)
    {
        $this->info("Syncing specific assistant: {$assistantId}");

        try {
            // Get assistant data from Vapi
            $vapiAssistant = $this->vapiService->getAssistant($assistantId);
            
            if (!$vapiAssistant) {
                $this->error("Assistant {$assistantId} not found in Vapi");
                return;
            }

            $this->processAssistant($vapiAssistant, $assistantId);
        } catch (\Exception $e) {
            $this->error("Failed to sync assistant {$assistantId}: " . $e->getMessage());
        }
    }

    /**
     * Sync all assistants for a specific user
     */
    protected function syncUserAssistants(string $userId)
    {
        $this->info("Syncing assistants for user ID: {$userId}");

        $user = User::find($userId);
        if (!$user) {
            $this->error("User {$userId} not found");
            return;
        }

        // Get user's assistants from local database
        $localAssistants = Assistant::where('user_id', $userId)->get();
        
        foreach ($localAssistants as $localAssistant) {
            try {
                $vapiAssistant = $this->vapiService->getAssistant($localAssistant->vapi_assistant_id);
                
                if ($vapiAssistant) {
                    $this->processAssistant($vapiAssistant, $localAssistant->vapi_assistant_id);
                } else {
                    $this->warn("Assistant {$localAssistant->vapi_assistant_id} not found in Vapi");
                }
            } catch (\Exception $e) {
                $this->error("Failed to sync assistant {$localAssistant->vapi_assistant_id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Sync all assistants in the system
     */
    protected function syncAllAssistants()
    {
        $this->info('Syncing all assistants...');

        // Get all assistants from local database
        $localAssistants = Assistant::all();
        
        $this->info("Found {$localAssistants->count()} assistants in local database");

        $synced = 0;
        $errors = 0;
        $created = 0;
        $updated = 0;

        // First, sync existing local assistants
        foreach ($localAssistants as $localAssistant) {
            try {
                $vapiAssistant = $this->vapiService->getAssistant($localAssistant->vapi_assistant_id);
                
                if ($vapiAssistant) {
                    $result = $this->processAssistant($vapiAssistant, $localAssistant->vapi_assistant_id);
                    if ($result === 'created') {
                        $created++;
                    } elseif ($result === 'updated') {
                        $updated++;
                    }
                    $synced++;
                } else {
                    $this->warn("Assistant {$localAssistant->vapi_assistant_id} not found in Vapi");
                    $errors++;
                }
            } catch (\Exception $e) {
                $this->error("Failed to sync assistant {$localAssistant->vapi_assistant_id}: " . $e->getMessage());
                $errors++;
            }
        }

        // Optionally sync assistants that exist in Vapi but not in local database
        if ($this->option('sync-missing')) {
            $this->syncMissingAssistants();
        }

        $this->info("Sync completed: {$synced} synced ({$created} created, {$updated} updated), {$errors} errors");
    }

    /**
     * Process and update assistant data
     */
    protected function processAssistant(array $vapiAssistant, string $assistantId)
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        // Check if assistant exists in local database
        $localAssistant = Assistant::where('vapi_assistant_id', $assistantId)->first();

        if ($dryRun) {
            if ($localAssistant) {
                $this->line("Would update assistant: {$vapiAssistant['name']} (ID: {$assistantId})");
            } else {
                $this->line("Would create assistant: {$vapiAssistant['name']} (ID: {$assistantId})");
            }
            return 'dry-run';
        }

        if ($localAssistant) {
            // Update existing assistant
            if ($force || $this->shouldUpdate($localAssistant, $vapiAssistant)) {
                $this->updateAssistant($localAssistant, $vapiAssistant);
                $this->info("Updated assistant: {$vapiAssistant['name']} (ID: {$assistantId})");
                return 'updated';
            } else {
                $this->line("Assistant {$vapiAssistant['name']} (ID: {$assistantId}) is up to date");
                return 'up-to-date';
            }
        } else {
            // Create new assistant
            $this->createAssistant($vapiAssistant);
            $this->info("Created assistant: {$vapiAssistant['name']} (ID: {$assistantId})");
            return 'created';
        }
    }

    /**
     * Determine if assistant should be updated
     */
    protected function shouldUpdate(Assistant $localAssistant, array $vapiAssistant): bool
    {
        // Check if name has changed
        if ($localAssistant->name !== $vapiAssistant['name']) {
            return true;
        }

        // Check if Vapi data has changed (you can add more specific checks here)
        $localVapiData = $localAssistant->vapi_data ?? [];
        $vapiData = $vapiAssistant;

        // Compare relevant fields
        $relevantFields = ['model', 'voice', 'firstMessage', 'endCallMessage'];
        
        foreach ($relevantFields as $field) {
            if (isset($vapiData[$field]) && (!isset($localVapiData[$field]) || $localVapiData[$field] !== $vapiData[$field])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update existing assistant
     */
    protected function updateAssistant(Assistant $localAssistant, array $vapiAssistant)
    {
        $localAssistant->update([
            'name' => $vapiAssistant['name'],
            'vapi_data' => $vapiAssistant,
            // Preserve existing user_id and created_by
        ]);
    }

    /**
     * Create new assistant
     */
    protected function createAssistant(array $vapiAssistant)
    {
        // Try to find the user by email in metadata
        $userEmail = $vapiAssistant['metadata']['user_email'] ?? null;
        $userId = null;
        $createdBy = null;

        if ($userEmail) {
            $user = User::where('email', $userEmail)->first();
            if ($user) {
                $userId = $user->id;
                $createdBy = $user->id;
            }
        }

        // If no user found, use the first admin user as fallback
        if (!$userId) {
            $adminUser = User::where('role', 'admin')->first();
            if ($adminUser) {
                $userId = $adminUser->id;
                $createdBy = $adminUser->id;
            }
        }

        Assistant::create([
            'name' => $vapiAssistant['name'],
            'user_id' => $userId,
            'vapi_assistant_id' => $vapiAssistant['id'],
            'created_by' => $createdBy,
            'type' => $vapiAssistant['metadata']['type'] ?? 'demo',
            'phone_number' => $vapiAssistant['metadata']['assistant_phone_number'] ?? null,
            'vapi_data' => $vapiAssistant,
        ]);
    }

    /**
     * Sync assistants that exist in Vapi but not in local database
     */
    protected function syncMissingAssistants()
    {
        $this->info('Syncing missing assistants from Vapi...');

        try {
            // Get all assistants from Vapi
            $vapiAssistants = $this->vapiService->getAssistants();
            
            if (!$vapiAssistants) {
                $this->warn('No assistants found in Vapi or unable to retrieve them');
                return;
            }

            $this->info("Found " . count($vapiAssistants) . " assistants in Vapi");

            $created = 0;
            $errors = 0;

            foreach ($vapiAssistants as $vapiAssistant) {
                try {
                    // Check if assistant already exists in local database
                    $existingAssistant = Assistant::where('vapi_assistant_id', $vapiAssistant['id'])->first();
                    
                    if (!$existingAssistant) {
                        if ($this->option('dry-run')) {
                            $this->line("Would create missing assistant: {$vapiAssistant['name']} (ID: {$vapiAssistant['id']})");
                        } else {
                            $this->createAssistant($vapiAssistant);
                            $this->info("Created missing assistant: {$vapiAssistant['name']} (ID: {$vapiAssistant['id']})");
                            $created++;
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to sync missing assistant {$vapiAssistant['id']}: " . $e->getMessage());
                    $errors++;
                }
            }

            $this->info("Missing assistants sync completed: {$created} created, {$errors} errors");
        } catch (\Exception $e) {
            $this->error("Failed to sync missing assistants: " . $e->getMessage());
        }
    }
}
