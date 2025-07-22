<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Assistant;
use App\Services\VapiService;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@voiceai.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
                'company' => 'Voice AI',
                'bio' => 'System Administrator',
            ]
        );

        // Create test user
        $testUser = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
                'company' => 'Test Company',
                'bio' => 'Test user for development',
            ]
        );

        // Import assistants from Vapi API
        $this->importVapiAssistants($adminUser, $testUser);
    }

    /**
     * Import assistants from Vapi API and map them to users
     */
    private function importVapiAssistants(User $adminUser, User $testUser): void
    {
        $vapiService = app(VapiService::class);
        
        try {
            $vapiAssistants = $vapiService->getAssistants();
            
            if (empty($vapiAssistants)) {
                $this->command->info('No assistants found in Vapi API');
                return;
            }

            $this->command->info("Found " . count($vapiAssistants) . " assistants in Vapi API");

            $importedCount = 0;
            $skippedCount = 0;

            foreach ($vapiAssistants as $vapiAssistant) {
                try {
                    $assistantId = $vapiAssistant['id'] ?? null;
                    $assistantName = $vapiAssistant['name'] ?? 'Unnamed Assistant';

                    if (!$assistantId) {
                        $this->command->warn("Skipping assistant without ID: " . $assistantName);
                        $skippedCount++;
                        continue;
                    }

                    // Check if assistant already exists in database
                    $existingAssistant = Assistant::where('vapi_assistant_id', $assistantId)->first();
                    if ($existingAssistant) {
                        $this->command->line("Assistant already exists: {$assistantName} (ID: {$assistantId})");
                        $skippedCount++;
                        continue;
                    }

                    // Determine user to assign the assistant to
                    $userId = $vapiAssistant['metadata']['user_id'] ?? null;
                    
                    if (!$userId) {
                        // Assign to admin user if no user_id found
                        $userId = $adminUser->id;
                    } else {
                        // Check if the user_id exists in our database
                        $user = User::find($userId);
                        if (!$user) {
                            // If user doesn't exist, assign to admin
                            $userId = $adminUser->id;
                        }
                    }

                    // Create assistant in database
                    Assistant::create([
                        'name' => $assistantName,
                        'user_id' => $userId,
                        'vapi_assistant_id' => $assistantId,
                        'created_by' => $userId,
                    ]);

                    $this->command->line("Imported: {$assistantName} (ID: {$assistantId})");
                    $importedCount++;

                } catch (\Exception $e) {
                    $error = "Error processing assistant {$assistantName}: " . $e->getMessage();
                    $this->command->error($error);
                    Log::error($error);
                }
            }

            $this->command->info("Import completed! Imported: {$importedCount}, Skipped: {$skippedCount}");

        } catch (\Exception $e) {
            $this->command->error("Error connecting to Vapi API: " . $e->getMessage());
            Log::error("Vapi API connection error: " . $e->getMessage());
        }
    }
}
