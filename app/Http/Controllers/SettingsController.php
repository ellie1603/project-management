<?php

namespace App\Http\Controllers;

use App\Models\ProjectCategory;
use App\Models\SystemSetting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * The system_settings keys editable from the Settings screen.
     *
     * @var array<int, string>
     */
    private const MANAGED_KEYS = [
        'organization_name',
        'currency',
        'project_registration_threshold',
        'budget_warning_threshold_percent',
    ];

    public function edit(): View
    {
        $this->authorize('manage', SystemSetting::class);

        $settings = SystemSetting::query()
            ->whereIn('key', self::MANAGED_KEYS)
            ->get()
            ->keyBy('key');

        $categories = ProjectCategory::query()->withCount('projects')->orderBy('name')->get();

        return view('settings.edit', compact('settings', 'categories'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('manage', SystemSetting::class);

        $validated = $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:10'],
            'project_registration_threshold' => ['required', 'numeric', 'min:0'],
            'budget_warning_threshold_percent' => ['required', 'numeric', 'min:1', 'max:100'],
        ]);

        foreach ($validated as $key => $value) {
            $setting = SystemSetting::query()->firstOrNew(['key' => $key]);
            $oldValue = $setting->value;
            $setting->value = (string) $value;
            $setting->save();

            app(AuditLogger::class)->record(
                $request,
                'updated',
                'system_settings',
                $setting->id,
                "Updated system setting {$key}.",
                ['value' => $oldValue],
                ['value' => $setting->value],
            );
        }

        return redirect()->route('settings.edit')->with('status', 'Settings updated successfully.');
    }
}
