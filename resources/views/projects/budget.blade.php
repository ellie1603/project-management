@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <a href="{{ route('projects.show', $project) }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors duration-150 hover:text-slate-900">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Back to Project
    </a>

    @include('projects.partials.budget')
</div>
@endsection
