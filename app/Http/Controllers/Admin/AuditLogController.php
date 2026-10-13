<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityAction;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Tampilkan daftar jejak audit aktivitas sistem dengan filter komprehensif.
     */
    public function index(Request $request): View
    {
        $query = ActivityLog::query()
            ->with('user')
            ->latest('id');

        // 1. Filter berdasarkan jenis tindakan (action)
        if ($request->filled('action')) {
            $actionValue = $request->input('action');
            if (ActivityAction::tryFrom($actionValue)) {
                $query->where('action', $actionValue);
            }
        }

        // 2. Filter berdasarkan aktor pengguna (user_id)
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // 3. Filter berdasarkan rentang tanggal
        if ($request->filled('start_date')) {
            try {
                $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
                $query->where('created_at', '>=', $startDate);
            } catch (\Exception) {
                // Abaikan format tanggal tidak valid
            }
        }

        if ($request->filled('end_date')) {
            try {
                $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
                $query->where('created_at', '<=', $endDate);
            } catch (\Exception) {
                // Abaikan format tanggal tidak valid
            }
        }

        // 4. Pencarian kata kunci pada deskripsi
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($sub) use ($search) {
                $sub->where('description', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(15)->withQueryString();

        // Data pendukung untuk formulir filter
        $users = User::query()
            ->select('id', 'name', 'username')
            ->orderBy('name')
            ->get();

        $actions = ActivityAction::cases();

        return view('admin.audit-logs.index', compact('logs', 'users', 'actions'));
    }
}
