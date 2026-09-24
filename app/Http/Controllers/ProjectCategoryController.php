<?php

namespace App\Http\Controllers;

use App\Models\ProjectCategory;
use App\Models\SystemSetting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectCategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage', SystemSetting::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:project_categories,name'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $category = ProjectCategory::create($validated);

        app(AuditLogger::class)->record(
            $request,
            'created',
            'project_categories',
            $category->id,
            "Created project category {$category->name}.",
            null,
            $category->toArray(),
        );

        return redirect()->route('settings.edit')->with('status', 'Project category created successfully.');
    }

    public function update(Request $request, ProjectCategory $category): RedirectResponse
    {
        $this->authorize('manage', SystemSetting::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('project_categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $oldValues = $category->toArray();
        $category->update($validated);

        app(AuditLogger::class)->record(
            $request,
            'updated',
            'project_categories',
            $category->id,
            "Updated project category {$category->name}.",
            $oldValues,
            $category->fresh()->toArray(),
        );

        return redirect()->route('settings.edit')->with('status', 'Project category updated successfully.');
    }

    public function destroy(Request $request, ProjectCategory $category): RedirectResponse
    {
        $this->authorize('manage', SystemSetting::class);

        if ($category->projects()->exists()) {
            return redirect()->route('settings.edit')->with('error', 'This category is in use by existing projects and cannot be removed.');
        }

        $oldValues = $category->toArray();
        $category->delete();

        app(AuditLogger::class)->record(
            $request,
            'deleted',
            'project_categories',
            $category->id,
            "Deleted project category {$oldValues['name']}.",
            $oldValues,
            null,
        );

        return redirect()->route('settings.edit')->with('status', 'Project category removed successfully.');
    }
}
