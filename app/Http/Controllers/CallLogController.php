<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CallLog;
use App\Models\Assistant;
use Illuminate\Support\Facades\Auth;

class CallLogController extends Controller
{
    /**
     * Get call logs with filtering
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = CallLog::query();

        // Filter by user's assistants only
        $userAssistantIds = Assistant::where('user_id', $user->id)->pluck('id');
        $query->whereIn('assistant_id', $userAssistantIds);

        // Apply filters
        if ($request->filled('assistant_id')) {
            $query->where('assistant_id', $request->assistant_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('start_time', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('start_time', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('call_id', 'like', "%{$search}%")
                  ->orWhere('transcript', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        // Order by most recent first
        $query->orderBy('start_time', 'desc');

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $callLogs = $query->paginate($perPage);

        // Filter out sensitive data for non-admin users
        $isAdmin = Auth::user()->is_admin ?? false;
        $callLogsData = $callLogs->items();
        
        if (!$isAdmin) {
            $callLogsData = collect($callLogsData)->map(function ($callLog) {
                unset($callLog->webhook_data);
                unset($callLog->metadata);
                unset($callLog->cost);
                unset($callLog->currency);
                return $callLog;
            })->toArray();
        }

        return response()->json([
            'success' => true,
            'data' => $callLogsData,
            'meta' => [
                'current_page' => $callLogs->currentPage(),
                'last_page' => $callLogs->lastPage(),
                'per_page' => $callLogs->perPage(),
                'total' => $callLogs->total(),
                'from' => $callLogs->firstItem(),
                'to' => $callLogs->lastItem(),
            ]
        ]);
    }

    /**
     * Get call logs statistics
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        $query = CallLog::query();

        // Filter by user's assistants only
        $userAssistantIds = Assistant::where('user_id', $user->id)->pluck('id');
        $query->whereIn('assistant_id', $userAssistantIds);

        // Apply date filters
        if ($request->filled('start_date')) {
            $query->whereDate('start_time', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('start_time', '<=', $request->end_date);
        }

        // Apply assistant filter
        if ($request->filled('assistant_id')) {
            $query->where('assistant_id', $request->assistant_id);
        }

        // Get basic stats
        $totalCalls = $query->count();
        $completedCalls = (clone $query)->where('status', 'completed')->count();
        $failedCalls = (clone $query)->whereIn('status', ['failed', 'busy', 'no-answer'])->count();
        $inboundCalls = (clone $query)->where('direction', 'inbound')->count();
        $outboundCalls = (clone $query)->where('direction', 'outbound')->count();
        $totalCost = (clone $query)->sum('cost');
        $averageDuration = (clone $query)->whereNotNull('duration')->avg('duration');

        // Get status breakdown
        $statusBreakdown = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Get assistant performance
        $assistantPerformance = $query->select(
                'assistant_id',
                DB::raw('count(*) as total_calls'),
                DB::raw('sum(case when status = "completed" then 1 else 0 end) as completed_calls'),
                DB::raw('avg(duration) as avg_duration'),
                DB::raw('sum(cost) as total_cost')
            )
            ->groupBy('assistant_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'totalCalls' => $totalCalls,
                'completedCalls' => $completedCalls,
                'failedCalls' => $failedCalls,
                'inboundCalls' => $inboundCalls,
                'outboundCalls' => $outboundCalls,
                'totalCost' => $totalCost,
                'averageDuration' => round($averageDuration ?? 0),
                'statusBreakdown' => $statusBreakdown,
                'assistantPerformance' => $assistantPerformance
            ]
        ]);
    }

    /**
     * Get specific call log details
     */
    public function show(Request $request, $callId)
    {
        $user = $request->user();
        
        // Get call log and ensure it belongs to user's assistant
        $callLog = CallLog::where('call_id', $callId)
            ->whereHas('assistant', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (!$callLog) {
            return response()->json([
                'success' => false,
                'message' => 'Call log not found'
            ], 404);
        }

        // Filter out sensitive data for non-admin users
        $isAdmin = Auth::user()->is_admin ?? false;
        $callLogData = $callLog->toArray();
        
        if (!$isAdmin) {
            unset($callLogData['webhook_data']);
            unset($callLogData['metadata']);
            unset($callLogData['cost']);
            unset($callLogData['currency']);
        }

        return response()->json([
            'success' => true,
            'data' => $callLogData
        ]);
    }
} 