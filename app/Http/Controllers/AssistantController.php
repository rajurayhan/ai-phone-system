<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use App\Services\VapiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        ]);

        $user = Auth::user();
        
        // Add user_id to metadata
        $data = $request->all();
        if (!isset($data['metadata'])) {
            $data['metadata'] = [];
        }
        $data['metadata']['user_id'] = $user->id;
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
            'user_id' => $user->id,
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

        // Update in database
        $assistant->update([
            'name' => $request->name,
        ]);

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

        // Delete from Vapi
        $vapiSuccess = $this->vapiService->deleteAssistant($assistant->vapi_assistant_id);

        if (!$vapiSuccess) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete assistant from Vapi'
            ], 500);
        }

        // Delete from database
        $assistant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Assistant deleted successfully'
        ]);
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