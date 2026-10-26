@extends('layouts.app')

@section('content')
@php
    $saved = [
        'profile-updated' => 'Your account details were saved.',
        'password-updated' => 'Your password was changed.',
        'notifications-updated' => 'Your notification choices were saved.',
    ][session('status')] ?? null;
    $card = 'rounded-2xl bg-white p-5 shadow-soft ring-1 ring-slate-900/5 sm:p-6';
@endphp

<div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <x-flash-toast :message="$saved" />

    <x-page-hero
        eyebrow="Account"
        title="My Account"
        subtitle="Your sign-in details, password, and which notifications you receive."
    />

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Who you are in the system; role and position are set by the administrator. --}}
        <aside class="{{ $card }} h-fit lg:sticky lg:top-24">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full chip-ink font-display text-xl font-semibold text-white">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="truncate font-display text-lg font-semibold text-slate-900">{{ $user->name }}</p>
                    <p class="truncate text-sm text-slate-500">{{ $user->email }}</p>
                </div>
            </div>

            <dl class="mt-5 divide-y divide-slate-100 text-sm">
                <div class="flex items-center justify-between gap-4 py-2.5">
                    <dt class="text-slate-500">Role</dt>
                    <dd class="text-right font-medium text-slate-900">{{ $user->roleLabel() }}</dd>
                </div>
                @if ($user->position_type)
                    <div class="flex items-center justify-between gap-4 py-2.5">
                        <dt class="text-slate-500">Position</dt>
                        <dd class="text-right font-medium text-slate-900">{{ $user->position_type }}</dd>
                    </div>
                @endif
                @if ($user->contractor)
                    <div class="flex items-center justify-between gap-4 py-2.5">
                        <dt class="text-slate-500">Company</dt>
                        <dd class="truncate text-right font-medium text-slate-900">{{ $user->contractor->name }}</dd>
                    </div>
                @endif
                <div class="flex items-center justify-between gap-4 py-2.5">
                    <dt class="text-slate-500">Member since</dt>
                    <dd class="text-right font-medium text-slate-900">{{ $user->created_at?->format('M j, Y') }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 py-2.5">
                    <dt class="text-slate-500">Previous sign-in</dt>
                    <dd class="text-right font-medium text-slate-900">{{ $lastSignIn ? \Illuminate\Support\Carbon::parse($lastSignIn)->format('M j, Y g:i A') : 'This is the first' }}</dd>
                </div>
            </dl>

            <p class="mt-4 flex gap-2 rounded-xl bg-slate-50 px-3 py-2.5 text-xs text-slate-500">
                <i data-lucide="info" class="mt-px h-3.5 w-3.5 shrink-0"></i>
                Your role and position are set by your administrator. Ask them if either is wrong, or if you need your account removed.
            </p>
        </aside>

        <div class="space-y-6 lg:col-span-2">
            <section class="{{ $card }}">@include('profile.partials.update-profile-information-form')</section>
            <section class="{{ $card }}">@include('profile.partials.update-password-form')</section>
            <section class="{{ $card }}">@include('profile.partials.update-notification-preferences-form')</section>
        </div>
    </div>
</div>
@endsection
