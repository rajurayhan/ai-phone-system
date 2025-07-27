<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SystemSettingController extends Controller
{
    /**
     * Get all system settings
     */
    public function index(): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $settings = SystemSetting::getGroupedSettings();

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Update system settings
     */
    public function update(Request $request): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
        ]);

        try {
            foreach ($request->settings as $setting) {
                $key = $setting['key'];
                $value = $setting['value'] ?? null;
                
                // Handle file uploads
                if (isset($setting['file']) && $setting['file']) {
                    $file = $setting['file'];
                    $path = $file->store('system', 'public');
                    $value = Storage::url($path);
                }
                
                SystemSetting::setValue($key, $value);
            }

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get public system settings (for frontend)
     */
    public function getPublicSettings(): JsonResponse
    {
        $settings = [
            'site_title' => SystemSetting::getValue('site_title', 'XpartFone'),
            'site_tagline' => SystemSetting::getValue('site_tagline', 'Revolutionary Voice AI Platform'),
            'meta_description' => SystemSetting::getValue('meta_description', 'Transform your business with cutting-edge voice AI technology'),
            'logo_url' => SystemSetting::getValue('logo_url'),
            'homepage_banner' => SystemSetting::getValue('homepage_banner'),
        ];

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }
}
