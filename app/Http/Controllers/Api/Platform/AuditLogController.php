<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user:id,name,email')
            ->orderByDesc('id');

        // School admins only see their own school's logs; super admins see all.
        $user = $request->user();
        if ($user && ! ($user->is_super_admin ?? false)) {
            $query->where('school_id', $user->school_id);
        }

        if ($request->query('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->query('school_id')) {
            $query->where('school_id', $request->query('school_id'));
        }

        if ($request->query('action')) {
            $query->where('action', $request->query('action'));
        }

        if ($request->query('entity_type')) {
            // Matches both FQCN (App\Models\Student) and short names (Student)
            $query->where('entity_type', 'like', '%'.$request->query('entity_type'));
        }

        if ($request->query('entity_id')) {
            $query->where('entity_id', $request->query('entity_id'));
        }

        if ($request->query('q')) {
            $query->where('description', 'like', '%'.$request->query('q').'%');
        }

        if ($request->query('from')) {
            $query->whereDate('created_at', '>=', $request->query('from'));
        }

        if ($request->query('to')) {
            $query->whereDate('created_at', '<=', $request->query('to'));
        }

        $logs = $query->paginate($request->integer('per_page', 20))->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Audit logs retrieved successfully',
            'data' => collect($logs->items())->map(function ($log) {
                $data = $log->toArray();
                $data['user_name'] = $log->user?->name;

                return $data;
            }),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
            ],
        ], 200);
    }
}