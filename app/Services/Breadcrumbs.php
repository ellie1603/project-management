<?php

namespace App\Services;

use App\Models\Contractor;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The breadcrumb trail for pages inside a module, worked out from the route
 * name and its bound records. A trail starts at the module the page belongs
 * to (never the Dashboard), and pages opened straight from the sidebar get
 * no trail at all. The last crumb is the current page and has no link.
 */
class Breadcrumbs
{
    /**
     * @return array<int, array{label: string, url: ?string}>
     */
    public function for(Request $request): array
    {
        $route = $request->route();
        $name = $route?->getName();

        if ($name === null) {
            return [];
        }

        $project = $route->parameter('project');
        $crumbs = array_values(array_filter($this->trail($name, $request, $request->user(), $project instanceof Project ? $project : null)));

        // A single crumb would only repeat the page title.
        if (count($crumbs) < 2) {
            return [];
        }

        $crumbs[array_key_last($crumbs)]['url'] = null;

        return $crumbs;
    }

    /**
     * @return array<int, array{label: string, url: ?string}|null>
     */
    private function trail(string $name, Request $request, ?User $user, ?Project $project): array
    {
        $projects = ['label' => $user?->isProjectPersonnel() ? 'My Projects' : 'Projects', 'url' => route('projects.index')];
        $projectCrumb = $project ? ['label' => Str::limit($project->title, 40), 'url' => route('projects.show', $project)] : null;
        $expenses = ['label' => 'Expenses', 'url' => route('budget.expenses')];
        // Finance opens Expenses and Budget Requests from the sidebar; for others they sit under Budget & Finance.
        $finance = $user?->isFinance() ? null : ['label' => 'Budget & Finance', 'url' => route('budget.overview')];

        return match ($name) {
            'projects.create' => [$projects, $this->page('Register Project')],
            'projects.show' => [$projects, $projectCrumb],
            'projects.edit' => [$projects, $projectCrumb, $this->page('Edit')],
            'projects.budget.show' => [$projects, $projectCrumb, $this->page('Budget')],
            'projects.contractors.index' => [$projects, $projectCrumb, $this->page('Contractors')],
            'contractors.show' => [
                $user?->hasPermission('users.manage') ? ['label' => 'Users', 'url' => route('users.index')] : $projects,
                $this->page($this->contractorName($request)),
            ],
            'budget.expenses' => [$finance, $this->page('Expenses')],
            'budget.expenses.batch' => [$expenses, $this->page('Add Expenses')],
            'budget.requests' => [$finance, $this->page('Budget Requests')],
            'users.create' => [['label' => 'Users', 'url' => route('users.index')], $this->page('New User')],
            'system.usage.user' => [
                ['label' => 'System Usage', 'url' => route('system.usage', $request->only(['from', 'to', 'role']))],
                $this->page($this->userName($request)),
            ],
            default => [],
        };
    }

    /**
     * @return array{label: string, url: null}
     */
    private function page(string $label): array
    {
        return ['label' => $label, 'url' => null];
    }

    private function contractorName(Request $request): string
    {
        $contractor = $request->route('contractor');

        return $contractor instanceof Contractor ? Str::limit($contractor->name, 40) : 'Provider';
    }

    private function userName(Request $request): string
    {
        $user = $request->route('user');

        return $user instanceof User ? $user->name : 'User';
    }
}
