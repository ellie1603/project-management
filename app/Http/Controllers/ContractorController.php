<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Models\Project;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ContractorController extends Controller
{
    public function show(Contractor $contractor): View
    {
        $this->authorize('view', $contractor);
        $contractor->load(['projects', 'user']);

        return view('contractors.show', compact('contractor'));
    }

    public function projectHistory(Project $project): View
    {
        $this->authorize('viewContractors', $project);
        $project->load(['contractors', 'quotations.contractor']);
        $availableContractors = Auth::user()->isAdmin()
            ? Contractor::query()->orderBy('name')->get()
            : collect();

        return view('projects.contractors', compact('project', 'availableContractors'));
    }

    public function attachToProject(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('manageContractors', $project);

        $validated = $request->validate([
            'contractor_id' => ['required', 'exists:contractors,id'],
            'role' => ['required', 'string', 'max:100'],
            'contract_amount' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'remarks' => ['nullable', 'string'],
        ]);

        $contractorId = $validated['contractor_id'];
        unset($validated['contractor_id']);
        $project->contractors()->syncWithoutDetaching([$contractorId => $validated]);
        $contractor = Contractor::findOrFail($contractorId);

        app(AuditLogger::class)->record(
            $request,
            'attached',
            'project_contractors',
            $project->id,
            "Attached contractor {$contractor->name} to {$project->title}.",
            null,
            [...$validated, 'contractor_id' => $contractorId],
        );

        return redirect()->route('projects.show', $project);
    }

    public function storeQuotation(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('manageContractors', $project);

        $validated = $request->validate([
            'contractor_id' => ['required', 'exists:contractors,id'],
            'quotation_amount' => ['required', 'numeric', 'min:0'],
            'quotation_date' => ['required', 'date'],
            'document_path' => ['nullable', 'string', 'max:500'],
            'remarks' => ['nullable', 'string'],
        ]);

        $quotation = $project->quotations()->create($validated);

        app(AuditLogger::class)->record(
            $request,
            'created',
            'contractor_quotations',
            $quotation->id,
            "Recorded a provider quotation for {$project->title}.",
            null,
            $quotation->toArray(),
        );

        return redirect()->route('projects.show', $project);
    }
}
