<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Makes the system installable as an app (phones and computers) while it
 * keeps working in any browser. One shareable link, /install, shows the right
 * way to install for the device it's opened on: the Windows desktop app when
 * published, the browser's install button on Android and desktop Chrome/Edge,
 * and "Add to Home Screen" steps on iPhone and iPad.
 */
class InstallAppController extends Controller
{
    public function manifest(): JsonResponse
    {
        return response()->json([
            'name' => 'BMPC Project Management',
            'short_name' => 'BMPC',
            'description' => 'Projects, budgets, expenses, and progress for Barbaza Multi-Purpose Cooperative.',
            'id' => '/',
            'start_url' => '/dashboard',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => '#ffffff',
            'theme_color' => '#0c2d6b',
            'icons' => [
                ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_SLASHES);
    }

    public function show(): View
    {
        $installerPath = config('desktop.installer_path');

        return view('install', [
            'windowsInstaller' => is_string($installerPath) && is_file($installerPath),
        ]);
    }
}
