<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserSignIn;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * System Usage for the System Administrator: how often each user signs in,
 * when they were last active, and how much they did, over a date range.
 * Actions come from the audit log, grouped into readable categories.
 */
class UsageReport
{
    /**
     * Audit-log modules (and optionally actions) behind each category.
     *
     * @var array<string, array{singular: string, plural: string, match: array<string, array<int, string>|null>}>
     */
    private const CATEGORIES = [
        'projects' => ['singular' => 'project change', 'plural' => 'project changes', 'match' => ['projects' => null]],
        'progress' => ['singular' => 'progress update', 'plural' => 'progress updates', 'match' => ['project_progress' => null]],
        'expenses' => ['singular' => 'expense', 'plural' => 'expenses', 'match' => ['project_expenses' => null]],
        'requests' => ['singular' => 'budget request', 'plural' => 'budget requests', 'match' => ['budget_requests' => ['created']]],
        'approvals' => ['singular' => 'approval decision', 'plural' => 'approval decisions', 'match' => ['budget_requests' => ['approved', 'rejected']]],
        'documents' => ['singular' => 'document', 'plural' => 'documents', 'match' => ['project_documents' => null]],
        'contractors' => ['singular' => 'contractor record', 'plural' => 'contractor records', 'match' => ['project_contractors' => null, 'contractor_quotations' => null]],
        'reports' => ['singular' => 'saved report', 'plural' => 'saved reports', 'match' => ['finance_reports' => null]],
        'administration' => ['singular' => 'admin change', 'plural' => 'admin changes', 'match' => ['users' => null, 'system_settings' => null, 'project_categories' => null, 'permissions' => null]],
    ];

    /**
     * One row per user (optionally one role) for the period.
     *
     * @return Collection<int, array{user: User, sign_ins: int, failed: int, last_sign_in: ?Carbon, actions: int, breakdown: array<int, string>}>
     */
    public function rows(Carbon $from, Carbon $to, ?string $role = null): Collection
    {
        $users = User::query()
            ->when($role, fn ($query) => $query->where('role', $role))
            ->orderByDesc('last_active_at')
            ->orderBy('name')
            ->get();

        $signIns = UserSignIn::query()
            ->whereNotNull('user_id')
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('event', ['login', 'failed'])
            ->selectRaw('user_id, event, count(*) as total')
            ->groupBy('user_id', 'event')
            ->get()
            ->groupBy('user_id');

        $lastSignIns = UserSignIn::query()
            ->where('event', 'login')
            ->whereNotNull('user_id')
            ->selectRaw('user_id, max(created_at) as latest')
            ->groupBy('user_id')
            ->pluck('latest', 'user_id');

        $actions = AuditLog::query()
            ->whereNotNull('user_id')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('user_id, module, action, count(*) as total')
            ->groupBy('user_id', 'module', 'action')
            ->get()
            ->groupBy('user_id');

        return $users->map(function (User $user) use ($signIns, $lastSignIns, $actions): array {
            $events = $signIns->get($user->id, collect())->pluck('total', 'event');
            $userActions = $actions->get($user->id, collect());

            return [
                'user' => $user,
                'sign_ins' => (int) ($events['login'] ?? 0),
                'failed' => (int) ($events['failed'] ?? 0),
                'last_sign_in' => isset($lastSignIns[$user->id]) ? Carbon::parse($lastSignIns[$user->id]) : null,
                'actions' => (int) $userActions->sum('total'),
                'breakdown' => $this->breakdown($userActions),
            ];
        });
    }

    /**
     * @param  Collection<int, array{sign_ins: int, failed: int, actions: int}>  $rows
     * @return array{active_users: int, sign_ins: int, failed: int, unknown_failed: int, actions: int}
     */
    public function totals(Collection $rows, Carbon $from, Carbon $to): array
    {
        return [
            'active_users' => $rows->filter(fn (array $row): bool => $row['sign_ins'] > 0 || $row['actions'] > 0)->count(),
            'sign_ins' => $rows->sum('sign_ins'),
            'failed' => $rows->sum('failed'),
            // Attempts with an email that belongs to no account.
            'unknown_failed' => UserSignIn::query()->whereNull('user_id')->where('event', 'failed')->whereBetween('created_at', [$from, $to])->count(),
            'actions' => $rows->sum('actions'),
        ];
    }

    /**
     * Category counts for one user's audit-log rows, largest first, e.g. "3 expenses".
     *
     * @param  Collection<int, AuditLog>  $actions  rows with module, action, total
     * @return array<int, string>
     */
    public function breakdown(Collection $actions): array
    {
        $counts = [];

        foreach ($actions as $row) {
            $category = $this->categoryFor($row->module, $row->action);
            $counts[$category] = ($counts[$category] ?? 0) + (int) $row->total;
        }

        arsort($counts);

        return collect($counts)->map(function (int $count, string $category): string {
            $definition = self::CATEGORIES[$category] ?? ['singular' => 'other action', 'plural' => 'other actions'];

            return $count.' '.($count === 1 ? $definition['singular'] : $definition['plural']);
        })->values()->all();
    }

    private function categoryFor(string $module, string $action): string
    {
        foreach (self::CATEGORIES as $key => $definition) {
            if (array_key_exists($module, $definition['match'])) {
                $actions = $definition['match'][$module];

                if ($actions === null || in_array($action, $actions, true)) {
                    return $key;
                }
            }
        }

        return 'other';
    }
}
