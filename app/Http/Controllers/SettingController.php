<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    /**
     * Get all settings by group
     */
    public function index(Request $request): JsonResponse
    {
        $group = $request->get('group', 'assistant_templates');
        
        $settings = Setting::byGroup($group)->get();
        
        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Get a specific setting
     */
    public function show($key): JsonResponse
    {
        $setting = Setting::where('key', $key)->first();
        
        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $setting
        ]);
    }

    /**
     * Update a setting (admin only)
     */
    public function update(Request $request, $key): JsonResponse
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'value' => 'required|string',
            'description' => 'nullable|string'
        ]);

        $setting = Setting::where('key', $key)->first();
        
        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found'
            ], 404);
        }

        $setting->value = $request->value;
        if ($request->has('description')) {
            $setting->description = $request->description;
        }
        $setting->save();

        return response()->json([
            'success' => true,
            'data' => $setting,
            'message' => 'Setting updated successfully'
        ]);
    }

    /**
     * Get assistant templates (public endpoint)
     */
    public function getAssistantTemplates(): JsonResponse
    {
        $templates = [
            'system_prompt' => Setting::getValue('assistant_system_prompt_template', ''),
            'first_message' => Setting::getValue('assistant_first_message_template', ''),
            'end_call_message' => Setting::getValue('assistant_end_call_message_template', '')
        ];

        return response()->json([
            'success' => true,
            'data' => $templates
        ]);
    }
} 