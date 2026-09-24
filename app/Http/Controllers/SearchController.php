<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $term = $request->string('q')->trim()->toString();
        $user = Auth::user();

        $projectsQuery = Project::query()->with('category');

        if ($user->isProjectPersonnel()) {
            $projectsQuery->whereHas('assignments', fn ($assignmentQuery) => $assignmentQuery->where('user_id', $user->id));
        }

        $projects = collect();
        $contractors = collect();
        $personnel = collect();

        if ($term !== '') {
            $projects = $projectsQuery
                ->where(function ($query) use ($term): void {
                    $query->where('project_code', 'like', "%{$term}%")
                        ->orWhere('title', 'like', "%{$term}%")
                        ->orWhere('location', 'like', "%{$term}%");
                })
                ->latest()
                ->limit(25)
                ->get();

            if ($user->isAdmin() || $user->isFinance()) {
                $contractors = Contractor::query()
                    ->where('name', 'like', "%{$term}%")
                    ->orderBy('name')
                    ->limit(25)
                    ->get();

                $personnel = User::query()
                    ->where('role', 'project_personnel')
                    ->where('name', 'like', "%{$term}%")
                    ->orderBy('name')
                    ->limit(25)
                    ->get();
            }
        }

        return view('search.results', compact('term', 'projects', 'contractors', 'personnel'));
    }
}
