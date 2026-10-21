# BMPC Project Management: Windows desktop app

A native Windows app for the BMPC Project Management System. It is a small launcher
(C#, .NET Framework 4.8) hosting **Microsoft Edge WebView2**, the same Chromium engine
as Edge. Users get their own window, taskbar icon and Start menu entry instead of a
browser tab.

## Two ways to run it

On first start the app asks which mode to use. It can be changed later from
**Desktop settings** (right-click the title bar, or use the Start menu shortcut).

| Mode | What runs | Use it when |
| --- | --- | --- |
| **Run on this computer (offline)** | A bundled private server: PHP 8.3, MySQL 8.4 and the Laravel app, bound to `127.0.0.1` only | One office PC, no server, no internet |
| **Connect to the BMPC server** | Only the app window; it opens the hosted server (e.g. the Dokploy deployment) | Several people share the same live data |

In offline mode the launcher creates the database, runs migrations and seeds the
default accounts on first start, applies new migrations after every update, and runs
the daily timeline alert check (`projects:check-alerts`) while the app is open.

## Two ways to deliver it: install, or not

`build.ps1` produces two files in `desktop/dist/`:

| File | What the user gets |
| --- | --- |
| `BMPC-ProjectManagement-<ver>-Setup.exe` | A setup wizard whose first page asks: **Install on this computer** (Start menu and desktop shortcuts, uninstaller, no admin rights needed) or **Portable** (only extracts the files to a folder; nothing is registered with Windows) |
| `BMPC-ProjectManagement-<ver>-Portable.zip` | No setup at all: unzip anywhere (including a USB drive) and run `BMPCProjectManagement.exe` |

### Install from the login page (one click)

The web login page can offer **"Install the desktop app?"** with **Install** and
**Not now** buttons. *Install* downloads a small installer (about 4 MB) that asks no
questions: once the user opens it from the browser's downloads, it shows a progress bar,
installs for the current user, adds the Start menu and desktop shortcuts, and opens the
app at the login page, already connected to the server.

```powershell
pwsh desktop\build.ps1 -WebInstaller -ServerUrl https://pm.example.coop
```

This builds the window-only installer preset to that address and copies it to
`storage/app/desktop/BMPC-Desktop-Setup.exe`, which the route `/desktop-app/download`
serves (see [config/desktop.php](../config/desktop.php); `DESKTOP_INSTALLER_PATH` overrides
the location). Without `-ServerUrl`, `APP_URL` from `.env` is used. On the Dokploy server,
copy the file into the storage volume at `app/desktop/`.

The offer appears only when that file exists, only in Windows browsers, and never inside
the desktop app. *Not now* hides it for 30 days in that browser. Browsers cannot run a
download on their own, so the user still opens the downloaded file once; until the
installer is code-signed, Windows SmartScreen also asks them to choose *More info* >
*Run anyway*.

Where data lives:

* **Installed:** `%LOCALAPPDATA%\BMPC Project Management\` (database, uploaded documents,
  logs, settings), separate for each Windows user. The uninstaller asks whether to delete it.
* **Portable:** a `data` folder next to the executable. The copy is portable because of the
  `portable.flag` file beside the executable.

## Requirements

**End users:** Windows 10 or 11 (64-bit) with the Microsoft Edge WebView2 Runtime. Windows 11
already includes it. The installer installs it if it is missing, and the portable copy
offers to.

**Building:** this repository with `composer install` done, plus:

* PHP 8.3+ (x64, thread safe) with the extensions in [runtime/php.ini](runtime/php.ini)
* MySQL 8.x for Windows (the ZIP archive build)
* Node.js (to build the Vite assets)
* PowerShell 7

Laragon provides all of these, and the script finds them in `C:\laragon\bin`
automatically. The C# compiler ships with Windows (.NET Framework 4.x). The WebView2 SDK,
the WebView2 bootstrapper and Inno Setup are downloaded into `desktop/.tools/` on the
first build, and their signatures are checked. Nothing is installed system-wide.

## Building

```powershell
pwsh desktop\build.ps1                       # full build: offline server + server mode
pwsh desktop\build.ps1 -Version 1.1.0        # set the version shown in Windows and the installer

# Smaller window-only build for a hosted server, with the address preset (no first-run question):
pwsh desktop\build.ps1 -Flavor client -ServerUrl https://pm.example.coop

pwsh desktop\build.ps1 -LauncherOnly         # recompile just the launcher into the existing stage
pwsh desktop\build.ps1 -SkipAssets           # reuse public\build instead of running Vite
pwsh desktop\build.ps1 -NoInstaller          # portable zip only (or -NoPortableZip)
```

Pass `-PhpDir`, `-MySqlDir` or `-NodeDir` to use runtimes outside Laragon.

What the full build does:

1. Compiles the launcher (`launcher/src/*.cs`) into `BMPCProjectManagement.exe`.
2. Runs `npm run build`, copies the app without dev files, tests, `.env` or storage, and runs
   `composer install --no-dev`.
3. Bundles PHP, keeping only the extensions it loads, and the parts of MySQL the app needs
   (server, admin, dump and client tools).
4. Checks that the bundled PHP starts and has every required extension.
5. Builds `Setup.exe` with Inno Setup ([installer/bmpc.iss](installer/bmpc.iss)) and the portable zip.

## Using the app

* **Default accounts** in a new offline database: `ceo@bmpc.test`, `sysadmin@bmpc.test`, `finance@bmpc.test`,
  `juan@bmpc.test`, `maria@bmpc.test` and `pedro@bmpc.test`, all with password `password123`.
  Change these passwords right after the first sign-in.
* **Title-bar menu:** right-click the title bar for Reload, Go to dashboard, Desktop settings,
  Open data folder and About.
* **Backups (offline mode):** Desktop settings > *Back up data...* writes one `.zip` holding a
  database dump and every uploaded document. *Restore backup...* replaces the current data with
  a backup and restarts the app; the previous documents folder is kept once as
  `storage\app.before-restore`.
* **Links:** links to other websites open in the default browser. Same-site links that open a
  new tab, such as PDF reports, open in a separate app window. Downloads such as PDF and Excel
  exports use the normal download bar.
* **Extra settings:** `desktop.json` in the data folder. `ExtraEnv` passes extra Laravel
  environment values to the offline server, for example
  `"ExtraEnv": { "GEMINI_API_KEY": "..." }` for the progress-report assistant.
  `"DevTools": true` turns on the browser developer tools.
* **Logs:** `logs\` in the data folder (`desktop.log`, `artisan.log`, `php-server.log`,
  `mysql-error.log`) and Laravel's own logs in `storage\logs\`.
* **Org-wide rollout:** build with `-ServerUrl`. The package then contains a
  `desktop.defaults.json` and users are never asked which mode to use.

## How offline mode works

```
BMPCProjectManagement.exe  (WinForms + WebView2)
 ├─ mysqld.exe   127.0.0.1:48766   data:  <data>\mysql
 └─ php.exe -S   127.0.0.1:48765   app:   <install>\app  (read-only)
                                   router: runtime\config\router.php
```

* All configuration reaches Laravel as environment variables. There is no `.env` file in the
  app folder. Writable paths (`LARAVEL_STORAGE_PATH`, `APP_*_CACHE`) point into the data
  folder, so the app folder can stay read-only under Program Files.
* `APP_KEY` and a random MySQL password are generated on first start and saved in
  `desktop.json`. MySQL accepts connections from `127.0.0.1` only, with X Protocol off.
* If a port is busy, the launcher picks another free one and remembers it.
* Child processes run in a Windows job object, so they stop when the app closes, even after
  a crash. MySQL is shut down cleanly with `mysqladmin shutdown` first.
* `php artisan optimize` runs again only when the app version or its environment changes.
* PHP's built-in server handles one request at a time. That is fine for one person on one
  PC; for several users, use server mode.

## Layout

```
desktop/
  build.ps1              build everything
  launcher/src/*.cs      launcher source (C# 5, compiled by the Windows csc.exe)
  launcher/app.ico       app icon (made from resources/logo/logo.png if missing)
  launcher/app.manifest  DPI awareness, Windows 10/11, no elevation
  runtime/php.ini        PHP settings for offline mode
  runtime/router.php     built-in server router (passes env vars to Laravel)
  installer/bmpc.iss     Inno Setup script with the Install / Portable choice
  .tools/ build/ dist/   downloaded tools, staging, output (git-ignored)
```
