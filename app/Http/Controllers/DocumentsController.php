<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ProjectDocument::class);

        $documents = ProjectDocument::query()
            ->with(['project:id,project_code,title', 'uploader:id,name'])
            ->when($request->filled('document_type'), fn ($query) => $query->where('document_type', $request->string('document_type')->toString()))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($nested) use ($search): void {
                    $nested->where('document_name', 'like', "%{$search}%")
                        ->orWhereHas('project', fn ($projectQuery) => $projectQuery->where('title', 'like', "%{$search}%")
                            ->orWhere('project_code', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $documentTypes = Project::DOCUMENT_TYPES;

        $summaryStats = [
            'total' => ProjectDocument::query()->count(),
            'current_designs' => ProjectDocument::query()->where('document_type', 'Approved Design')->where('is_current', true)->count(),
            'storage_bytes' => (int) ProjectDocument::query()->sum('file_size'),
            'uploaded_this_week' => ProjectDocument::query()->where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return $this->respond($request, 'documents.index', 'documents.partials.results', compact('documents', 'documentTypes', 'summaryStats'));
    }
}
