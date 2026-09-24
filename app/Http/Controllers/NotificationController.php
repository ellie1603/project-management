<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * The latest notifications for the bell-icon dropdown (no pagination —
     * this is a transient dropdown, not a standalone page).
     */
    public function recent(): View
    {
        $notifications = Auth::user()->notifications()->latest()->take(10)->get();

        return view('notifications.partials.dropdown', compact('notifications'));
    }

    public function markAsRead(string $notification): Response
    {
        $record = Auth::user()->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        return response()->noContent();
    }

    public function markAllAsRead(): RedirectResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back();
    }
}
