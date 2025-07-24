<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use App\Services\VapiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User; // Added this import for User model

class AssistantController extends Controller
{
    protected $vapiService;

    public function __construct(VapiService $vapiService)
    {
        $this->vapiService = $vapiService;
    }

    /**
     * Get all assistants for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = Assistant::with(['user', 'creator']);
        
        // If user is admin, show all assistants
        if ($user->isAdmin()) {
            // No additional filtering needed
        } else {
            // For regular users, only show their own assistants
            $query->forUser($user->id);
        }
        
        // Search by name
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }
        
        // Sort by name (default) or other fields
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if (in_array($sortBy, ['name', 'created_at', 'user_id'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc'); // Default fallback
        }
        
        $assistants = $query->get();

        // Return basic database data without Vapi details for list view
        return response()->json([
            'success' => true,
            'data' => $assistants
        ]);
    }

    /**
     * Get all assistants (admin only)
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Assistant::with(['user', 'creator']);
        
        // Search by assistant name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }
        
        // Search by user (owner) name or email
        if ($request->filled('user_search')) {
            $userSearch = $request->user_search;
            $query->whereHas('user', function ($q) use ($userSearch) {
                $q->where('name', 'like', "%{$userSearch}%")
                  ->orWhere('email', 'like', "%{$userSearch}%");
            });
        }
        
        // Sort by name (default) or other fields
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if (in_array($sortBy, ['name', 'created_at', 'user_id'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc'); // Default fallback
        }
        
        $assistants = $query->get();

        return response()->json([
            'success' => true,
            'data' => $assistants
        ]);
    }

    /**
     * Create a new assistant
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:40',
            'model' => 'required|array',
            'voice' => 'required|array',
            'firstMessage' => 'string|max:1000',
            'endCallMessage' => 'string|max:1000',
            'metadata' => 'array',
            'metadata.company_name' => 'string|max:255',
            'metadata.industry' => 'string|max:255',
            'metadata.services_products' => 'string|max:1000',
            'metadata.sms_phone_number' => 'string|max:20',
            'metadata.assistant_phone_number' => 'nullable|string|max:20|regex:/^\+[1-9]\d{1,14}$/',
            'metadata.webhook_url' => 'nullable|url|max:500',
            'user_id' => 'nullable|integer|exists:users,id', // Allow admin to assign to specific user
            'type' => 'nullable|string|in:demo,production', // New type field
            'selected_phone_number' => 'nullable|string|max:20|regex:/^\+[1-9]\d{1,14}$/', // Allow selected phone number
        ]);

        $user = Auth::user();
        
        // Check if user can create more assistants (skip for admin users)
        if (!$user->isAdmin()) {
            if (!$user->canCreateAssistant()) {
                if (!$user->hasActiveSubscription()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You need an active subscription to create assistants. Please subscribe to a plan to get started.'
                    ], 403);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'You have reached your assistant limit for your current subscription plan. Please upgrade your plan to create more assistants.'
                    ], 403);
                }
            }
        }
        
        // Determine the user_id for the assistant
        $assistantUserId = $user->id; // Default to current user
        
        // If admin is creating and has specified a user_id, use that
        if ($user->isAdmin() && $request->has('user_id') && $request->user_id) {
            $assistantUserId = $request->user_id;
            
            // For admins, we don't check subscription limits of target users
            // Admins can create assistants for any user regardless of their subscription status
        }
        
        // Add user_id to metadata
        $data = $request->all();
        if (!isset($data['metadata'])) {
            $data['metadata'] = [];
        }
        $data['metadata']['user_id'] = $assistantUserId;
        $data['voice']['voiceId'] = 'Spencer';

        // Handle phone number purchase and assignment
        $phoneNumber = null;
        if ($request->has('selected_phone_number') && $request->selected_phone_number) {
            $twilioService = app(\App\Services\TwilioService::class);
            
            // Purchase the selected phone number
            $purchaseResult = $twilioService->purchaseNumber($request->selected_phone_number);
            
            if ($purchaseResult['success']) {
                $phoneNumber = $request->selected_phone_number;
                $data['metadata']['assistant_phone_number'] = $phoneNumber;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to purchase phone number: ' . ($purchaseResult['message'] ?? 'Unknown error')
                ], 500);
            }
        }

        // Create assistant in Vapi
        $vapiAssistant = $this->vapiService->createAssistant($data);

        if (!$vapiAssistant) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create assistant in Vapi'
            ], 500);
        }

        // Store in database
        $assistant = Assistant::create([
            'name' => $data['name'],
            'user_id' => $assistantUserId,
            'vapi_assistant_id' => $vapiAssistant['id'],
            'created_by' => $user->id,
            'type' => $data['type'] ?? 'demo', // Default to demo
            'phone_number' => $data['metadata']['assistant_phone_number'] ?? null,
            'webhook_url' => $data['metadata']['webhook_url'] ?? 'https://n8n.cloud.lhgdev.com/webhook/lhg-live-demo-agents',
        ]);

        // Assign phone number to Vapi if purchased
        if ($phoneNumber) {
            $this->vapiService->assignPhoneNumber($vapiAssistant['id'], $phoneNumber);
        }

        return response()->json([
            'success' => true,
            'data' => array_merge($assistant->toArray(), [
                'vapi_data' => $vapiAssistant
            ]),
            'message' => 'Assistant created successfully'
        ], 201);
    }

    /**
     * Get a specific assistant
     */
    public function show(Request $request, $assistantId): JsonResponse
    {
        $user = Auth::user();
        
        // Find assistant in database
        $assistant = Assistant::where('vapi_assistant_id', $assistantId)
            // ->orWhere('id', $assistantId)
            ->with(['user', 'creator'])
            ->first();

        

        Log::info('Assistant found', ['assistant' => $assistant]);

        if (!$assistant) {
            return response()->json([
                'success' => false,
                'message' => 'Assistant not found'
            ], 404);
        }

        // Check if user owns this assistant or is admin
        if (!$user->isAdmin() && $assistant->user_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Get detailed data from Vapi
        $vapiData = $this->vapiService->getAssistant($assistant->vapi_assistant_id);

        // Synchronize webhook URL between Vapi and local database
        $vapiWebhookUrl = null;
        if (isset($vapiData['server']['url'])) {
            $vapiWebhookUrl = $vapiData['server']['url'];
        }

        // If Vapi has webhook URL but local database doesn't, use Vapi's value
        if ($vapiWebhookUrl && !$assistant->webhook_url) {
            $assistant->update(['webhook_url' => $vapiWebhookUrl]);
        }

        // Merge webhook URL from database with Vapi metadata for frontend
        if ($assistant->webhook_url && isset($vapiData['metadata'])) {
            $vapiData['metadata']['webhook_url'] = $assistant->webhook_url;
        }

        return response()->json([
            'success' => true,
            'data' => array_merge($assistant->toArray(), [
                'vapi_data' => $vapiData
            ])
        ]);
    }

    /**
     * Update an assistant
     */
    public function update(Request $request, $assistantId): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:40',
            'model' => 'required|array',
            'voice' => 'required|array',
            'firstMessage' => 'string|max:1000',
            'endCallMessage' => 'string|max:1000',
            'metadata' => 'array',
            'metadata.company_name' => 'string|max:255',
            'metadata.industry' => 'string|max:255',
            'metadata.services_products' => 'string|max:1000',
            'metadata.sms_phone_number' => 'string|max:20',
            'metadata.assistant_phone_number' => 'nullable|string|max:20|regex:/^\+[1-9]\d{1,14}$/',
            'metadata.webhook_url' => 'nullable|url|max:500',
            'user_id' => 'nullable|integer|exists:users,id', // Allow admin to reassign to different user
            'type' => 'nullable|string|in:demo,production', // New type field
            'selected_phone_number' => 'nullable|string|max:20|regex:/^\+[1-9]\d{1,14}$/', // Allow selected phone number
        ]);

        $user = Auth::user();
        
        // Find assistant in database
        $assistant = Assistant::where('vapi_assistant_id', $assistantId)
            // ->orWhere('id', $assistantId)
            ->first();

        if (!$assistant) {
            return response()->json([
                'success' => false,
                'message' => 'Assistant not found'
            ], 404);
        }

        // Check if user owns this assistant or is admin
        if (!$user->isAdmin() && $assistant->user_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // If this is a demo assistant, use templates from settings
        if ($assistant->isDemo()) {
            $templates = \App\Models\Setting::getValue('assistant_system_prompt_template', '');
            $firstMessageTemplate = \App\Models\Setting::getValue('assistant_first_message_template', '');
            $endCallMessageTemplate = \App\Models\Setting::getValue('assistant_end_call_message_template', '');
            
            // Replace template variables with actual company data
            $companyName = $request->input('metadata.company_name', '');
            $companyIndustry = $request->input('metadata.industry', '');
            $companyServices = $request->input('metadata.services_products', '');
            
            $processedSystemPrompt = str_replace(
                ['{{company_name}}', '{{company_industry}}', '{{company_services}}'],
                [$companyName, $companyIndustry, $companyServices],
                $templates
            );
            
            $processedFirstMessage = str_replace(
                ['{{company_name}}', '{{company_industry}}', '{{company_services}}'],
                [$companyName, $companyIndustry, $companyServices],
                $firstMessageTemplate
            );
            
            $processedEndCallMessage = str_replace(
                ['{{company_name}}', '{{company_industry}}', '{{company_services}}'],
                [$companyName, $companyIndustry, $companyServices],
                $endCallMessageTemplate
            );
            
            // Update the request data with processed templates
            $request->merge([
                'model' => array_merge($request->input('model', []), [
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $processedSystemPrompt
                        ]
                    ]
                ]),
                'firstMessage' => $processedFirstMessage,
                'endCallMessage' => $processedEndCallMessage
            ]);
        }

        // Update in Vapi
        $vapiData = $this->vapiService->updateAssistant($assistant->vapi_assistant_id, $request->all());

        if (!$vapiData) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update assistant in Vapi'
            ], 500);
        }

        // Prepare update data
        $updateData = [
            'name' => $request->name,
        ];
        
        // Synchronize webhook URL from Vapi response
        $vapiWebhookUrl = null;
        if (isset($vapiData['server']['url'])) {
            $vapiWebhookUrl = $vapiData['server']['url'];
        }

        // If Vapi has webhook URL but local database doesn't, use Vapi's value
        if ($vapiWebhookUrl && !$assistant->webhook_url) {
            $updateData['webhook_url'] = $vapiWebhookUrl;
        }
        
        // If admin is updating and has specified a new user_id, update it
        if ($user->isAdmin() && $request->has('user_id') && $request->user_id && $request->user_id != $assistant->user_id) {
            $updateData['user_id'] = $request->user_id;
        }
        
        // Update type if provided
        if ($request->has('type')) {
            $updateData['type'] = $request->type;
        }
        
        // Update phone_number if provided
        if ($request->has('metadata.assistant_phone_number')) {
            $updateData['phone_number'] = $request->input('metadata.assistant_phone_number');
        }
        
        // Update webhook_url if provided
        if ($request->has('metadata.webhook_url')) {
            $updateData['webhook_url'] = $request->input('metadata.webhook_url');
        }

        // Update in database
        $assistant->update($updateData);

        return response()->json([
            'success' => true,
            'data' => array_merge($assistant->toArray(), [
                'vapi_data' => $vapiData
            ]),
            'message' => 'Assistant updated successfully'
        ]);
    }

    /**
     * Delete an assistant
     */
    public function destroy(Request $request, $assistantId): JsonResponse
    {
        $user = Auth::user();
        
        \Log::info('Delete assistant request', [
            'user_id' => $user->id,
            'assistant_id' => $assistantId,
            'user_role' => $user->role
        ]);
        
        // Find assistant in database
        $assistant = Assistant::where('vapi_assistant_id', $assistantId)
            // ->orWhere('id', $assistantId)
            ->first();

        if (!$assistant) {
            \Log::warning('Assistant not found for deletion', ['assistant_id' => $assistantId]);
            return response()->json([
                'success' => false,
                'message' => 'Assistant not found'
            ], 404);
        }

        // Check if user owns this assistant or is admin
        if (!$user->isAdmin() && $assistant->user_id != $user->id) {
            \Log::warning('Unauthorized delete attempt', [
                'user_id' => $user->id,
                'assistant_user_id' => $assistant->user_id,
                'assistant_id' => $assistantId
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this assistant'
            ], 403);
        }

        try {
            // Delete from Vapi first
            \Log::info('Deleting assistant from Vapi', [
                'vapi_assistant_id' => $assistant->vapi_assistant_id
            ]);
            
            $vapiSuccess = $this->vapiService->deleteAssistant($assistant->vapi_assistant_id);

            if (!$vapiSuccess) {
                \Log::error('Failed to delete assistant from Vapi', [
                    'vapi_assistant_id' => $assistant->vapi_assistant_id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete assistant from Vapi server. Please try again.'
                ], 500);
            }

            \Log::info('Successfully deleted from Vapi, now deleting from database', [
                'assistant_id' => $assistant->id,
                'vapi_assistant_id' => $assistant->vapi_assistant_id
            ]);

            // Delete from database
            $assistant->delete();

            \Log::info('Assistant deleted successfully', [
                'assistant_id' => $assistant->id,
                'vapi_assistant_id' => $assistant->vapi_assistant_id,
                'deleted_by_user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assistant deleted successfully from both system and Vapi server'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Exception during assistant deletion', [
                'assistant_id' => $assistant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the assistant: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get assistant statistics
     */
    public function stats(Request $request, $assistantId): JsonResponse
    {
        $user = Auth::user();
        
        // Find assistant in database
        $assistant = Assistant::where('vapi_assistant_id', $assistantId)
            // ->orWhere('id', $assistantId)
            ->first();

        if (!$assistant) {
            return response()->json([
                'success' => false,
                'message' => 'Assistant not found'
            ], 404);
        }

        // Check if user owns this assistant or is admin
        if (!$user->isAdmin() && $assistant->user_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $stats = $this->vapiService->getAssistantStats($assistant->vapi_assistant_id);

        if (!$stats) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get assistant statistics'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
} 