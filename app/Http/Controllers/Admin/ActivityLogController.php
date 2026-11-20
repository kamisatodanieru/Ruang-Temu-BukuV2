<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ActivityLog::class);

        $search = trim((string) $request->query('search', ''));
        $actionFilter = (string) $request->query('action', '');

        $logs = ActivityLog::query()
            ->select([
                'id',
                'user_id',
                'action',
                'subject_type',
                'subject_id',
                'description',
                'ip_address',
                'created_at',
            ])
            ->with('user:id,name,email')
            ->when($search !== '', function ($query) use ($search) {
                $escaped = addcslashes($search, '%_\\');
                $query->where(function ($subQuery) use ($escaped) {
                    $subQuery->where('description', 'like', "%{$escaped}%")
                        ->orWhere('action', 'like', "%{$escaped}%")
                        ->orWhereHas('user', function ($userQuery) use ($escaped) {
                            $userQuery->where('name', 'like', "%{$escaped}%")
                                ->orWhere('email', 'like', "%{$escaped}%");
                        });
                });
            })
            ->when($actionFilter !== '', function ($query) use ($actionFilter) {
                $query->where('action', $actionFilter);
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $availableActions = ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'availableActions' => $availableActions,
            'filters' => [
                'search' => $search,
                'action' => $actionFilter,
            ],
        ]);
    }
}
