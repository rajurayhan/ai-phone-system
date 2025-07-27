<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DemoRequestController extends Controller
{
    /**
     * Submit a demo request
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'company_name' => 'required|string|max:255',
            'industry' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'services' => 'required|string|max:1000',
        ]);

        try {
            $demoRequest = DemoRequest::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'company_name' => $request->company_name,
                'industry' => $request->industry,
                'country' => $request->country,
                'services' => $request->services,
                'status' => 'pending',
            ]);

            Log::info('Demo request submitted', [
                'id' => $demoRequest->id,
                'name' => $demoRequest->name,
                'email' => $demoRequest->email,
                'company' => $demoRequest->company_name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demo request submitted successfully! We will contact you within 24 hours.',
                'data' => $demoRequest
            ], 201);
        } catch (\Exception $e) {
            Log::error('Demo request submission failed', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit demo request. Please try again.'
            ], 500);
        }
    }

    /**
     * Admin: Get all demo requests with filtering
     */
    public function adminIndex(Request $request): JsonResponse
    {
        \Log::info('DemoRequestController::adminIndex called', [
            'user_id' => auth()->id(),
            'user_role' => auth()->user()->role ?? 'unknown',
            'request_params' => $request->all()
        ]);

        $query = DemoRequest::query();

        // Log the initial query
        \Log::info('Initial query count: ' . $query->count());

        // Filter by status
        if ($request->has('status') && $request->status !== '' && $request->status !== null && $request->status !== 'null') {
            $query->where('status', $request->status);
            \Log::info('Applied status filter: ' . $request->status);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from && $request->date_from !== '' && $request->date_from !== 'null') {
            $query->whereDate('created_at', '>=', $request->date_from);
            \Log::info('Applied date_from filter: ' . $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to && $request->date_to !== '' && $request->date_to !== 'null') {
            $query->whereDate('created_at', '<=', $request->date_to);
            \Log::info('Applied date_to filter: ' . $request->date_to);
        }

        // Search by name, email, or company
        if ($request->has('search') && $request->search && $request->search !== '' && $request->search !== 'null') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%");
            });
            \Log::info('Applied search filter: ' . $search);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Log the final query count
        \Log::info('Final query count: ' . $query->count());

        // Paginate
        $perPage = $request->get('per_page', 15);
        $demoRequests = $query->paginate($perPage);

        \Log::info('Paginated result', [
            'total' => $demoRequests->total(),
            'count' => $demoRequests->count(),
            'current_page' => $demoRequests->currentPage()
        ]);

        return response()->json([
            'success' => true,
            'data' => $demoRequests
        ]);
    }

    /**
     * Admin: Get demo request statistics
     */
    public function adminStats(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        $stats = [
            'total' => DemoRequest::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'pending' => DemoRequest::whereBetween('created_at', [$dateFrom, $dateTo])->pending()->count(),
            'contacted' => DemoRequest::whereBetween('created_at', [$dateFrom, $dateTo])->contacted()->count(),
            'completed' => DemoRequest::whereBetween('created_at', [$dateFrom, $dateTo])->completed()->count(),
            'cancelled' => DemoRequest::whereBetween('created_at', [$dateFrom, $dateTo])->cancelled()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Admin: Update demo request status
     */
    public function updateStatus(Request $request, DemoRequest $demoRequest): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,contacted,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        $updateData = [
            'status' => $request->status,
        ];

        // Update timestamps based on status
        if ($request->status === 'contacted' && $demoRequest->status !== 'contacted') {
            $updateData['contacted_at'] = now();
        } elseif ($request->status === 'completed' && $demoRequest->status !== 'completed') {
            $updateData['completed_at'] = now();
        }

        if ($request->has('notes')) {
            $updateData['notes'] = $request->notes;
        }

        $demoRequest->update($updateData);

        Log::info('Demo request status updated', [
            'id' => $demoRequest->id,
            'status' => $request->status,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demo request status updated successfully',
            'data' => $demoRequest->fresh()
        ]);
    }

    /**
     * Admin: Get single demo request
     */
    public function show(DemoRequest $demoRequest): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $demoRequest
        ]);
    }

    /**
     * Admin: Delete demo request
     */
    public function destroy(DemoRequest $demoRequest): JsonResponse
    {
        $demoRequest->delete();

        Log::info('Demo request deleted', [
            'id' => $demoRequest->id,
            'deleted_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demo request deleted successfully'
        ]);
    }
} 