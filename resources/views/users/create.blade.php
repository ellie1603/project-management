@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('users.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors duration-150 hover:text-slate-900">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Back to Users
        </a>
        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Administration</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">New User</h1>
    </div>

    <form method="POST" action="{{ route('users.store') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf

        @include('users._form')

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('users.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Create User</button>
        </div>
    </form>
</div>
@endsection
