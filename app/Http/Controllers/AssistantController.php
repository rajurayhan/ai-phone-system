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
            'user_id' => 'nullable|integer|exists:users,id', // Allow admin to assign to specific user
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
            
            // Check if the target user can create more assistants
            $targetUser = User::find($assistantUserId);
            if (!$targetUser->canCreateAssistant()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected user has reached their assistant limit for their current subscription plan.'
                ], 403);
            }
        }
        
        // Add user_id to metadata
        $data = $request->all();
        if (!isset($data['metadata'])) {
            $data['metadata'] = [];
        }
        $data['metadata']['user_id'] = $assistantUserId;
        $data['voice']['voiceId'] = 'Spencer';

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
        ]);

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
            ->orWhere('id', $assistantId)
            ->with(['user', 'creator'])
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

        // Get detailed data from Vapi
        $vapiData = $this->vapiService->getAssistant($assistant->vapi_assistant_id);

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
            'user_id' => 'nullable|integer|exists:users,id', // Allow admin to reassign to different user
        ]);

        $user = Auth::user();
        
        // Find assistant in database
        $assistant = Assistant::where('vapi_assistant_id', $assistantId)
            ->orWhere('id', $assistantId)
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
        
        // If admin is updating and has specified a new user_id, update it
        if ($user->isAdmin() && $request->has('user_id') && $request->user_id && $request->user_id != $assistant->user_id) {
            $updateData['user_id'] = $request->user_id;
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
            ->orWhere('id', $assistantId)
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
            ->orWhere('id', $assistantId)
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