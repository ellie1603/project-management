<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Permissions;
use App\Services\UsageReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * System Usage (System Administrator only): sign-ins, last activity, and
 * actions per user over a date range.
 */
class SystemUsageController extends Controller
{
    public function __construct(private readonly UsageReport $report) {}

    public function index(Request $request): View
    {
        [$from, $to, $filters] = $this->period($request);
        $role = $filters['role'] ?? null;

        $rows = $this->report->rows($from, $to, $role);
        $totals = $this->report->totals($rows, $from, $to);

        return view('system.usage', [
            'rows' => $rows,
            'totals' => $totals,
            'from' => $from,
            'to' => $to,
            'role' => $role,
            'roles' => Permissions::ROLES,
        ]);
    }

    public function show(Request $request, User $user): View
    {
        [$from, $to] = $this->period($request);

        $signIns = $user->signIns()->whereBetween('created_at', [$from, $to])->latest('created_at')->limit(100)->get();
        $actions = AuditLog::query()->where('user_id', $user->id)->whereBetween('created_at', [$from, $to])->latest()->limit(50)->get();
        $summary = $this->report->rows($from, $to)->firstWhere('user.id', $user->id);

        return view('system.usage-user', compact('user', 'signIns', 'actions', 'summary', 'from', 'to'));
    }

    /**
     * The reporting period from the query string; the last 30 days by default.
     *
     * @return array{0: Carbon, 1: Carbon, 2: array<string, mixed>}
     */
    private function period(Request $request): array
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'role' => ['nullable', Rule::in(array_keys(Permissions::ROLES))],
        ]);

        $to = isset($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : now()->endOfDay();
        $from = isset($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : $to->copy()->subDays(29)->startOfDay();

        return [$from, $to, $filters];
    }
}
