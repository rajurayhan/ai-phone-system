<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CallLog;
use App\Models\Assistant;

class CallLogController extends Controller
{
    /**
     * Get all call logs with filtering (admin only)
     */
    public function index(Request $request)
    {
        $query = CallLog::with(['assistant.user']);

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

        if ($request->filled('phone_number')) {
            $query->where(function ($q) use ($request) {
                $q->where('phone_number', 'like', '%' . $request->phone_number . '%')
                  ->orWhere('caller_number', 'like', '%' . $request->phone_number . '%');
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('call_id', 'like', '%' . $search . '%')
                  ->orWhere('transcript', 'like', '%' . $search . '%')
                  ->orWhere('summary', 'like', '%' . $search . '%');
            });
        }

        // Order by most recent first
        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 15);
        $callLogs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $callLogs->items(),
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
     * Get call logs statistics (admin only)
     */
    public function stats(Request $request)
    {
        $query = CallLog::query();

        // Apply filters
        if ($request->filled('assistant_id')) {
            $query->where('assistant_id', $request->assistant_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('start_time', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('start_time', '<=', $request->end_date);
        }

        // Get basic stats
        $totalCalls = (clone $query)->count();
        $completedCalls = (clone $query)->where('status', 'completed')->count();
        $failedCalls = (clone $query)->whereIn('status', ['failed', 'busy', 'no-answer'])->count();
        $averageDuration = (clone $query)->where('status', 'completed')->avg('duration');

        // Get status breakdown
        $statusBreakdown = (clone $query)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Get direction breakdown
        $directionBreakdown = (clone $query)
            ->select('direction', DB::raw('count(*) as count'))
            ->groupBy('direction')
            ->pluck('count', 'direction')
            ->toArray();

        // Get top performing assistants
        $topAssistants = (clone $query)
            ->select(
                'assistant_id',
                DB::raw('count(*) as total_calls'),
                DB::raw('sum(case when status = "completed" then 1 else 0 end) as completed_calls')
            )
            ->groupBy('assistant_id')
            ->having('total_calls', '>', 0)
            ->orderBy('total_calls', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                // Load assistant data separately
                $assistant = Assistant::with('user')->find($item->assistant_id);
                $item->assistant_name = $assistant ? $assistant->name : 'Unknown Assistant';
                $item->assistant_user_name = $assistant && $assistant->user ? $assistant->user->name : 'Unknown User';
                $item->completion_rate = $item->total_calls > 0 
                    ? round(($item->completed_calls / $item->total_calls) * 100, 1)
                    : 0;
                return $item;
            });

        // Get cost analysis
        $costAnalysis = (clone $query)
            ->select(
                DB::raw('sum(cost) as total_cost'),
                DB::raw('avg(cost) as average_cost'),
                DB::raw('max(cost) as highest_cost')
            )
            ->whereNotNull('cost')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'totalCalls' => $totalCalls,
                'completedCalls' => $completedCalls,
                'failedCalls' => $failedCalls,
                'averageDuration' => $averageDuration,
                'statusBreakdown' => $statusBreakdown,
                'directionBreakdown' => $directionBreakdown,
                'topAssistants' => $topAssistants,
                'costAnalysis' => $costAnalysis
            ]
        ]);
    }

    /**
     * Get specific call log details (admin only)
     */
    public function show(Request $request, $callId)
    {
        $callLog = CallLog::with(['assistant.user'])
            ->where('call_id', $callId)
            ->first();

        if (!$callLog) {
            return response()->json([
                'success' => false,
                'message' => 'Call log not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $callLog
        ]);
    }
} 