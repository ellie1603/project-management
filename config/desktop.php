<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Desktop App Installer
    |--------------------------------------------------------------------------
    |
    | The Windows installer offered on the login page ("Install the desktop
    | app"). Build it with:
    |
    |     pwsh desktop\build.ps1 -WebInstaller -ServerUrl https://your-server
    |
    | which copies it to the default path below. The prompt is hidden while
    | no installer file exists, so nothing is offered until one is published.
    |
    */

    'installer_path' => env('DESKTOP_INSTALLER_PATH', storage_path('app/desktop/BMPC-Desktop-Setup.exe')),

    // File name the browser saves the download as.
    'download_name' => 'BMPC-Desktop-Setup.exe',

];
