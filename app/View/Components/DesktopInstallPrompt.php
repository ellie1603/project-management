<?php

namespace App\View\Components;

use Illuminate\Http\Request;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * "Install the desktop app?" card on the login page.
 *
 * Shown only to Windows browsers, only when an installer has been published,
 * and never inside the desktop app itself (its user agent ends in BMPCDesktop/x.y.z).
 */
class DesktopInstallPrompt extends Component
{
    public function __construct(private readonly Request $request) {}

    public function shouldRender(): bool
    {
        $userAgent = (string) $this->request->userAgent();

        return is_file($this->installerPath())
            && str_contains($userAgent, 'Windows')
            && ! str_contains($userAgent, 'BMPCDesktop/');
    }

    public function render(): View
    {
        return view('components.desktop-install-prompt', [
            'downloadSize' => max(1, (int) round(filesize($this->installerPath()) / 1048576))."\u{00A0}MB", // keep '4 MB' on one line
        ]);
    }

    private function installerPath(): string
    {
        return (string) config('desktop.installer_path');
    }
}
