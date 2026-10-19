<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves the one-click Windows installer for the desktop app, offered on the
 * login page. The installer is built separately (desktop\build.ps1 -WebInstaller).
 */
class DesktopInstallerController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        $path = config('desktop.installer_path');

        abort_unless(is_string($path) && is_file($path), 404);

        return response()->download($path, config('desktop.download_name'), [
            'Content-Type' => 'application/vnd.microsoft.portable-executable',
        ]);
    }
}
