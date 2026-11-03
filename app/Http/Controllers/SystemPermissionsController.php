<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Roles & Permissions (System Administrator only): which abilities each of
 * the three roles has. Roles are listed with their counts and edited one at
 * a time in a modal.
 */
class SystemPermissionsController extends Controller
{
    public function edit(): View
    {
        $users = User::query()
            ->selectRaw('role, is_system_admin, count(*) as total')
            ->groupBy('role', 'is_system_admin')
            ->get();

        // The System Administrator shares the admin role, so it is counted apart from the CEO.
        $count = fn (string $role, bool $systemAdmin = false): int => (int) $users
            ->filter(fn (User $row): bool => $row->role === $role && ($role !== 'admin' || (bool) $row->is_system_admin === $systemAdmin))
            ->sum('total');

        return view('system.permissions', [
            'catalog' => Permissions::catalog(),
            'roles' => Permissions::ROLES,
            'granted' => Permissions::grantedMap(),
            'systemAdminCount' => $count('admin', true),
            'userCounts' => collect(Permissions::ROLES)->map(fn (string $label, string $role): int => $count($role))->all(),
        ]);
    }

    public function update(Request $request, string $role): RedirectResponse
    {
        abort_unless(array_key_exists($role, Permissions::ROLES), 404);

        // Unticked boxes are not sent, so a role with nothing ticked has no "permissions" at all.
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(Permissions::all()))],
        ]);

        try {
            $changes = Permissions::saveRole($role, $validated['permissions'] ?? []);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['permissions' => $exception->getMessage()])->withInput();
        }

        $roleLabel = Permissions::ROLES[$role];

        if ($changes === []) {
            return redirect()->route('system.permissions')->with('status', "No changes to {$roleLabel} permissions.");
        }

        $lines = array_map(
            fn (array $change): string => ($change['granted'] ? 'allowed' : 'removed').' "'.Permissions::label($change['permission']).'"',
            $changes,
        );

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated',
            'module' => 'permissions',
            'record_id' => null,
            'description' => "Changed {$roleLabel} permissions: ".implode('; ', $lines).'.',
            'old_values' => null,
            'new_values' => $changes,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('system.permissions')->with('status', $roleLabel.' permissions saved ('.count($changes).' '.(count($changes) === 1 ? 'change' : 'changes').').');
    }
}
