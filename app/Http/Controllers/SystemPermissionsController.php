<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Roles & Permissions (System Administrator only): which abilities each of
 * the three roles has.
 */
class SystemPermissionsController extends Controller
{
    public function edit(): View
    {
        return view('system.permissions', [
            'catalog' => Permissions::catalog(),
            'roles' => Permissions::ROLES,
            'granted' => Permissions::grantedMap(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // Unticked boxes are not sent, so a role with nothing ticked is simply absent.
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['array'],
            'permissions.*.*' => ['string', Rule::in(array_keys(Permissions::all()))],
        ]);

        $matrix = collect($validated['permissions'] ?? [])->only(array_keys(Permissions::ROLES))->all();

        try {
            $changes = Permissions::save($matrix);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['permissions' => $exception->getMessage()]);
        }

        if ($changes === []) {
            return redirect()->route('system.permissions')->with('status', 'No changes to save.');
        }

        $lines = array_map(
            fn (array $change): string => Permissions::ROLES[$change['role']].': '.($change['granted'] ? 'allowed' : 'removed').' "'.Permissions::label($change['permission']).'"',
            $changes,
        );

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated',
            'module' => 'permissions',
            'record_id' => null,
            'description' => 'Changed role permissions. '.implode('; ', $lines).'.',
            'old_values' => null,
            'new_values' => $changes,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('system.permissions')->with('status', count($changes).' permission '.(count($changes) === 1 ? 'change' : 'changes').' saved.');
    }
}
