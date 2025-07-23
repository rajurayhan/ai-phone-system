<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    /**
     * Get all active features
     */
    public function index(): JsonResponse
    {
        $features = Feature::active()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $features
        ]);
    }

    /**
     * Admin: Get all features
     */
    public function adminIndex(): JsonResponse
    {
        $features = Feature::ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $features
        ]);
    }

    /**
     * Admin: Create a new feature
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'icon' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $feature = Feature::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $feature,
            'message' => 'Feature created successfully'
        ], 201);
    }

    /**
     * Admin: Update a feature
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'icon' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $feature = Feature::findOrFail($id);
        $feature->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $feature,
            'message' => 'Feature updated successfully'
        ]);
    }

    /**
     * Admin: Delete a feature
     */
    public function destroy($id): JsonResponse
    {
        $feature = Feature::findOrFail($id);
        $feature->delete();

        return response()->json([
            'success' => true,
            'message' => 'Feature deleted successfully'
        ]);
    }
}
