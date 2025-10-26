<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query()->with('user')->latest();

        $filters = [
            'action' => $request->string('action')->toString(),
            'user_id' => $request->string('user_id')->toString(),
            'ip_address' => $request->string('ip_address')->toString(),
            'status' => $request->string('status')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
        ];

        if ($filters['action']) {
            $query->where('action', 'like', "%{$filters['action']}%");
        }

        if ($filters['user_id']) {
            $query->where('user_id', $filters['user_id']);
        }

        if ($filters['ip_address']) {
            $query->where('ip_address', 'like', "%{$filters['ip_address']}%");
        }

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['date_from']) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $perPage = max(1, $request->integer('per_page', 20));
        $logs = $query->paginate($perPage)->appends(array_filter($filters));

        if ($request->wantsJson()) {
            $collection = $logs->getCollection()->map(function (AuditLog $log) {
                $data = $log->toArray();

                if ($log->user) {
                    $data['user'] = [
                        'id' => method_exists($log->user, 'getAuthIdentifier') ? $log->user->getAuthIdentifier() : $log->user->id ?? null,
                        'type' => $log->user::class,
                        'name' => $log->user->name ?? $log->user->username ?? null,
                        'email' => $log->user->email ?? null,
                    ];
                }

                return $data;
            });

            return response()->json([
                'data' => $collection,
                'meta' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                ],
            ]);
        }

        return view('admin.audit-logs.index', [
            'page_title' => 'Audit Logs',
            'logs' => $logs,
            'filters' => $filters,
        ]);
    }
}
