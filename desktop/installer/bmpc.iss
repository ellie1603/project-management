; BMPC Project Management - Windows installer.
;
; One Setup.exe, two choices on the first page:
;   Install  - Start menu and desktop shortcuts, an uninstaller, data in %LOCALAPPDATA%.
;   Portable - just extracts the app to a folder (a USB drive works); nothing is
;              registered with Windows and all data stays in that folder.
;
; Built by desktop\build.ps1, which defines AppVersion, StageDir, OutputDir,
; OutputBaseFilename and IconFile. Silent use:
;   Setup.exe /VERYSILENT                         install for the current user
;   Setup.exe /VERYSILENT /ALLUSERS               install for all users (admin)
;   Setup.exe /VERYSILENT /PORTABLE=1 /DIR="E:\BMPC"   portable copy
;
; Defining AutoInstall (build.ps1 -WebInstaller) makes the one-click installer
; offered on the login page: no questions, just a progress bar, then the app opens.

#define AppName "BMPC Project Management"
#define AppPublisher "Barbaza Multi-Purpose Cooperative"
#define ExeName "BMPCProjectManagement.exe"

#ifndef AppVersion
  #define AppVersion "1.0.0"
#endif

; Longest install folder that keeps every packaged file under Windows' 260-character
; path limit. build.ps1 calculates it from the staged files.
#ifndef MaxDirLength
  #define MaxDirLength "110"
#endif

[Setup]
AppId={{6F3B1C52-8E2A-4C7B-9A41-2D5E8B7C1F09}
AppName={#AppName}
AppVersion={#AppVersion}
AppVerName={#AppName} {#AppVersion}
AppPublisher={#AppPublisher}
VersionInfoVersion={#AppVersion}
VersionInfoProductName={#AppName}
DefaultDirName={autopf}\{#AppName}
DefaultGroupName={#AppName}
DisableProgramGroupPage=yes
DirExistsWarning=no
; Per-user by default, so no administrator rights are needed. /ALLUSERS installs machine-wide.
PrivilegesRequired=lowest
PrivilegesRequiredOverridesAllowed=commandline
ArchitecturesAllowed=x64compatible
ArchitecturesInstallIn64BitMode=x64compatible
MinVersion=10.0
OutputDir={#OutputDir}
OutputBaseFilename={#OutputBaseFilename}
SetupIconFile={#IconFile}
UninstallDisplayIcon={app}\{#ExeName}
UninstallDisplayName={#AppName}
Uninstallable=not IsPortable
CreateUninstallRegKey=not IsPortable
UpdateUninstallLogAppName=no
Compression=lzma2/max
SolidCompression=yes
WizardStyle=modern
; Held by the running app; setup asks the user to close it first.
AppMutex=BMPCProjectManagementRunning
#ifdef AutoInstall
DisableDirPage=yes
DisableReadyPage=yes
DisableFinishedPage=yes
#endif


[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Check: not IsPortable

[InstallDelete]
; Program files are replaced on every upgrade. User data lives elsewhere
; (or in {app}\data for a portable copy) and is never touched.
Type: filesandordirs; Name: "{app}\app"
Type: filesandordirs; Name: "{app}\runtime"

[Files]
Source: "{#StageDir}\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs

[Icons]
Name: "{autoprograms}\{#AppName}"; Filename: "{app}\{#ExeName}"; Check: not IsPortable
Name: "{autoprograms}\{#AppName} - Desktop Settings"; Filename: "{app}\{#ExeName}"; Parameters: "--settings"; Check: not IsPortable
Name: "{autodesktop}\{#AppName}"; Filename: "{app}\{#ExeName}"; Tasks: desktopicon; Check: not IsPortable

[Run]
Filename: "{app}\redist\MicrosoftEdgeWebview2Setup.exe"; Parameters: "/silent /install"; StatusMsg: "Installing the Microsoft Edge WebView2 Runtime..."; Check: NeedsWebView2; Flags: waituntilterminated
Filename: "{app}\{#ExeName}"; Description: "Open {#AppName} now"; Flags: nowait postinstall skipifsilent; Check: not IsAutoInstall
Filename: "{app}\{#ExeName}"; Flags: nowait; Check: IsAutoInstall

[Code]
const
  WebView2ClientKey = 'Microsoft\EdgeUpdate\Clients\{F3017226-FE2A-4295-8BDF-00C3A9A7E4C5}';

var
  TypePage: TInputOptionWizardPage;
  InstallDir: String;

function IsAutoInstall: Boolean;
begin
#ifdef AutoInstall
  Result := True;
#else
  Result := False;
#endif
end;

function IsPortable: Boolean;
begin
  if TypePage = nil then
    Result := ExpandConstant('{param:PORTABLE|0}') = '1'
  else
    Result := TypePage.Values[1];
end;

function HasWebView2Version(RootKey: Integer; SubKey: String): Boolean;
var
  Version: String;
begin
  Result := RegQueryStringValue(RootKey, SubKey, 'pv', Version) and (Version <> '') and (Version <> '0.0.0.0');
end;

function NeedsWebView2: Boolean;
begin
  Result := not (HasWebView2Version(HKLM, 'SOFTWARE\WOW6432Node\' + WebView2ClientKey)
    or HasWebView2Version(HKLM, 'SOFTWARE\' + WebView2ClientKey)
    or HasWebView2Version(HKCU, 'Software\' + WebView2ClientKey));
end;

// The one-click installer asks nothing: question pages are skipped, and the
// Ready page (which Inno Setup shows whenever every earlier page was skipped)
// presses Install by itself. Only the progress bar is really seen.
function ShouldSkipPage(PageID: Integer): Boolean;
begin
  Result := IsAutoInstall and ((PageID = TypePage.ID) or (PageID = wpSelectTasks));
end;
procedure InitializeWizard;
begin
  TypePage := CreateInputOptionPage(wpWelcome,
    'Installation type',
    'Install the app, or run it without installing?',
    'Install: adds Start menu and desktop shortcuts and an uninstaller. Your data is kept in your Windows user profile.' + #13#10#13#10 +
    'Portable: copies the app into a folder you choose (a USB drive works too). Nothing is added to Windows, and the database, documents and settings stay inside that folder.',
    True, False);
  TypePage.Add('Install on this computer (recommended)');
  TypePage.Add('Portable - extract to a folder, no installation');

  if ExpandConstant('{param:PORTABLE|0}') = '1' then
    TypePage.Values[1] := True
  else
    TypePage.Values[0] := True;

  InstallDir := WizardForm.DirEdit.Text;
end;

function NextButtonClick(CurPageID: Integer): Boolean;
begin
  Result := True;

  if (CurPageID = wpSelectDir) and (Length(WizardDirValue) > {#MaxDirLength}) then
  begin
    MsgBox('This folder path is too long for Windows (' + IntToStr(Length(WizardDirValue)) + ' characters; the limit for this app is {#MaxDirLength}).' + #13#10#13#10 +
      'Choose a shorter location, for example ' + ExpandConstant('{userdesktop}') + ' or the root of a USB drive.', mbError, MB_OK);
    Result := False;
    Exit;
  end;

  // Setup "clicks" Next in silent mode too: only suggest a folder when the
  // user is choosing interactively and did not pass /DIR.
  if (CurPageID = TypePage.ID) and not WizardSilent and (ExpandConstant('{param:DIR|}') = '') then
  begin
    if IsPortable then
      WizardForm.DirEdit.Text := ExpandConstant('{userdesktop}\{#AppName} Portable')
    else
      WizardForm.DirEdit.Text := InstallDir;
  end;
end;

function UpdateReadyMemo(Space, NewLine, MemoUserInfoInfo, MemoDirInfo, MemoTypeInfo, MemoComponentsInfo, MemoGroupInfo, MemoTasksInfo: String): String;
begin
  if IsPortable then
    Result := 'Installation type:' + NewLine + Space + 'Portable (no installation)'
  else
    Result := 'Installation type:' + NewLine + Space + 'Install on this computer';

  Result := Result + NewLine + NewLine + MemoDirInfo;

  if MemoTasksInfo <> '' then
    Result := Result + NewLine + NewLine + MemoTasksInfo;
end;


procedure CurStepChanged(CurStep: TSetupStep);
var
  Flag: String;
begin
  if CurStep <> ssPostInstall then
    Exit;

  Flag := ExpandConstant('{app}\portable.flag');

  if IsPortable then
    SaveStringToFile(Flag,
      'This copy is portable: settings, database and documents are kept in the "data" folder next to it.' + #13#10 +
      'Delete this file to make the app use %LOCALAPPDATA%\{#AppName} instead.' + #13#10, False)
  else if FileExists(Flag) then
    DeleteFile(Flag);
end;

procedure CurPageChanged(CurPageID: Integer);
begin
  if IsAutoInstall and (CurPageID = wpReady) then
    PostMessage(WizardForm.NextButton.Handle, $00F5 { BM_CLICK }, 0, 0);

  if (CurPageID = wpFinished) and IsPortable then
    WizardForm.FinishedLabel.Caption :=
      'The portable copy is ready in:' + #13#10 + WizardForm.DirEdit.Text + #13#10#13#10 +
      'Start it with {#ExeName} in that folder. To remove it, delete the folder.';
end;

procedure CurUninstallStepChanged(CurUninstallStep: TUninstallStep);
var
  DataDir: String;
begin
  if (CurUninstallStep <> usPostUninstall) or UninstallSilent then
    Exit;

  DataDir := ExpandConstant('{localappdata}\{#AppName}');

  if DirExists(DataDir) then
    if MsgBox('Also delete the BMPC data stored for this Windows user (offline database, uploaded documents and settings)?' + #13#10#13#10 +
      'Choose No to keep it for a later reinstall.', mbConfirmation, MB_YESNO or MB_DEFBUTTON2) = IDYES then
      DelTree(DataDir, True, True, True);
end;
