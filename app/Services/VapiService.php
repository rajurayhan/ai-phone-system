<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class VapiService
{
    protected $baseUrl = 'https://api.vapi.ai';
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.vapi.api_key');
        
        if (!$this->apiKey) {
            Log::error('Vapi API key not configured. Please add VAPI_API_KEY to your .env file.');
        }
    }

    /**
     * Get all assistants for a user
     */
    public function getAssistants(User $user = null)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/assistant');

            if ($response->successful()) {
                $assistants = $response->json();
                
                // Filter assistants by user if provided
                // if ($user) {
                //     $assistants = array_filter($assistants, function($assistant) use ($user) {
                //         return isset($assistant['metadata']['user_id']) && 
                //                $assistant['metadata']['user_id'] == $user->id;
                //     });
                // }

                return $assistants;
            }

            Log::error('Vapi API Error: ' . $response->body());
            Log::error('Vapi API Status: ' . $response->status());
            Log::error('Vapi API Headers: ' . json_encode($response->headers()));
            return [];
        } catch (\Exception $e) {
            Log::error('Vapi Service Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a specific assistant
     */
    public function getAssistant($assistantId)
    {
        try {
            // Log::info('Vapi Get Assistant Request: ' . $assistantId);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/assistant/' . $assistantId);

            // Log::info('Vapi Get Assistant Response Status: ' . $response->status());
            // Log::info('Vapi Get Assistant Response Body: ' . $response->body());

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Vapi Get Assistant Error: ' . $response->body());
            // Return a fallback structure if Vapi is not available
            return [
                'id' => $assistantId,
                'name' => 'Assistant',
                'status' => 'unknown',
                'model' => ['provider' => 'unknown'],
                'voice' => ['provider' => 'unknown'],
                'metadata' => []
            ];
        } catch (\Exception $e) {
            Log::error('Vapi Get Assistant Service Error: ' . $e->getMessage());
            // Return a fallback structure if Vapi is not available
            return [
                'id' => $assistantId,
                'name' => 'Assistant',
                'status' => 'unknown',
                'model' => ['provider' => 'unknown'],
                'voice' => ['provider' => 'unknown'],
                'metadata' => []
            ];
        }
    }

    /**
     * Create a new assistant
     */
    public function createAssistant(array $data)
    {
        try {
            // Prepare the create data according to Vapi API structure
            $createData = [
                'name' => $data['name'],
                'model' => $data['model'],
                'voice' => $data['voice'],
                'firstMessage' => $data['firstMessage'] ?? '',
                'endCallMessage' => $data['endCallMessage'] ?? '',
                'metadata' => array_merge($data['metadata'] ?? [], [
                    'created_at' => now()->toISOString(),
                    'type' => $data['type'],
                ])
            ];

            // Add server configuration for webhook URL
            if (!empty($data['metadata']['webhook_url'])) {
                $createData['server'] = [
                    'url' => $data['metadata']['webhook_url']
                ];
            }

            // Add serverMessages for end-of-call report
            $createData['serverMessages'] = [
                'end-of-call-report'
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/assistant', $createData);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Vapi Create Assistant Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Vapi Create Assistant Service Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Assign a phone number to an assistant
     */
    public function assignPhoneNumber($assistantId, $phoneNumber)
    {
        try {
            Log::info('Vapi Assign Phone Number Request', [
                'assistantId' => $assistantId,
                'phoneNumber' => $phoneNumber
            ]);

            $data = [
                'provider' => 'twilio',
                'number' => $phoneNumber,
                'twilioAccountSid' => config('services.twilio.account_sid'),
                'twilioAuthToken' => config('services.twilio.auth_token'),
                'assistantId' => $assistantId,
                'name' => 'Twilio Number'
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/phone-number', $data);

            if ($response->successful()) {
                Log::info('Vapi Assign Phone Number Success', [
                    'assistantId' => $assistantId,
                    'phoneNumber' => $phoneNumber,
                    'response' => $response->json()
                ]);
                return $response->json();
            }

            Log::error('Vapi Assign Phone Number Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Vapi Assign Phone Number Service Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update an assistant
     */
    public function updateAssistant($assistantId, array $data)
    {
        try {
            // First, get the current assistant data from Vapi to preserve existing values
            $currentAssistant = $this->getAssistant($assistantId);
            
            // Build update data with only the fields that Vapi accepts for updates
            // (same fields as create method)
            $updateData = [
                'name' => $data['name'] ?? $currentAssistant['name'],
                'model' => $data['model'] ?? $currentAssistant['model'],
                'voice' => $data['voice'] ?? $currentAssistant['voice'],
                'firstMessage' => $data['firstMessage'] ?? $currentAssistant['firstMessage'] ?? '',
                'endCallMessage' => $data['endCallMessage'] ?? $currentAssistant['endCallMessage'] ?? '',
                'metadata' => array_merge($currentAssistant['metadata'] ?? [], $data['metadata'] ?? []),
                'serverMessages' => $currentAssistant['serverMessages'] ?? [
                    'end-of-call-report'
                ]
            ];
            
            // Update the updated_at timestamp
            $updateData['metadata']['updated_at'] = now()->toISOString();
            
            // Handle webhook URL specifically - only add server if webhook URL is provided
            if (isset($data['metadata']['webhook_url'])) {
                if (!empty($data['metadata']['webhook_url'])) {
                    $updateData['server'] = [
                        'url' => $data['metadata']['webhook_url']
                    ];
                } else {
                    // Remove server configuration if webhook URL is empty
                    $updateData['server'] = null;
                }
            } elseif (isset($currentAssistant['server'])) {
                // Preserve existing server configuration if not provided in update
                $updateData['server'] = $currentAssistant['server'];
            }

            // Debug logging
            Log::info('Sending update to Vapi:', [
                'assistant_id' => $assistantId,
                'update_data' => $updateData,
                'original_data' => $currentAssistant,
                'request_data' => $data
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->put($this->baseUrl . '/assistant/' . $assistantId, $updateData);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Vapi Update Assistant Error: ' . $response->body());
            Log::error('Vapi Update Assistant Status: ' . $response->status());
            Log::error('Vapi Update Assistant Headers: ' . json_encode($response->headers()));
            return null;
        } catch (\Exception $e) {
            Log::error('Vapi Update Assistant Service Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete an assistant
     */
    public function deleteAssistant($assistantId)
    {
        try {
            Log::info('Attempting to delete assistant from Vapi', ['assistant_id' => $assistantId]);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->delete($this->baseUrl . '/assistant/' . $assistantId);

            Log::info('Vapi delete response', [
                'assistant_id' => $assistantId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                Log::info('Successfully deleted assistant from Vapi', ['assistant_id' => $assistantId]);
                return true;
            }

            Log::error('Vapi Delete Assistant Error', [
                'assistant_id' => $assistantId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Vapi Delete Assistant Service Error', [
                'assistant_id' => $assistantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get assistant statistics
     */
    public function getAssistantStats($assistantId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/assistant/' . $assistantId . '/stats');

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Vapi Get Assistant Stats Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Vapi Get Assistant Stats Service Error: ' . $e->getMessage());
            return null;
        }
    }
} 