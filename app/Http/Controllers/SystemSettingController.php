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
            'settings' => 'required|string', // JSON string
            'logo_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'banner_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB for banner
        ]);

        try {
            // Parse settings JSON
            $settings = json_decode($request->settings, true);
            
            foreach ($settings as $setting) {
                $key = $setting['key'];
                $value = $setting['value'] ?? null;
                
                SystemSetting::setValue($key, $value);
            }
            
            // Handle logo file upload
            if ($request->hasFile('logo_file')) {
                $file = $request->file('logo_file');
                $path = $file->store('system/logos', 'public');
                $url = Storage::disk('public')->url($path);
                SystemSetting::setValue('logo_url', $url);
            }
            
            // Handle banner file upload
            if ($request->hasFile('banner_file')) {
                $file = $request->file('banner_file');
                $path = $file->store('system/banners', 'public');
                $url = Storage::disk('public')->url($path);
                SystemSetting::setValue('homepage_banner', $url);
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
            'site_title' => SystemSetting::getValue('site_title', 'SulusAI'),
            'site_tagline' => SystemSetting::getValue('site_tagline', 'Never Miss a call Again SulusAI answers 24x7! Voice AI Platform'),
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
