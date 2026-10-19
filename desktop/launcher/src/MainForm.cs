using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.Drawing;
using System.Linq;
using System.Reflection;
using System.Runtime.InteropServices;
using System.Threading.Tasks;
using System.Windows.Forms;
using Microsoft.Web.WebView2.Core;
using Microsoft.Web.WebView2.WinForms;

namespace Bmpc.Desktop
{
    /// <summary>The app window: a WebView2 browser locked to the BMPC server (local or hosted).</summary>
    internal sealed class MainForm : Form
    {
        public static readonly int ActivateMessage = NativeMethods.RegisterWindowMessage("BMPC_ACTIVATE_" + AppPaths.InstanceId);
        public static readonly int SettingsMessage = NativeMethods.RegisterWindowMessage("BMPC_SETTINGS_" + AppPaths.InstanceId);

        private const int CmdReload = 0x1010;
        private const int CmdHome = 0x1020;
        private const int CmdSettings = 0x1030;
        private const int CmdDataFolder = 0x1040;
        private const int CmdAbout = 0x1050;

        // Context menu entries that make no sense inside a desktop app.
        private static readonly HashSet<string> HiddenMenuItems = new HashSet<string>
        {
            "inspectElement", "viewPageSource", "saveAs", "share", "webCapture", "createQrCode",
            "sendTabToDevices", "openLinkInNewWindow", "copyLinkToHighlight",
        };

        private readonly DesktopConfig config;
        private readonly WebView2 webView;
        private readonly SplashPanel splash;
        private CoreWebView2Environment environment;
        private LocalServer server;
        private Uri baseUri;
        private bool shownApp;
        private bool starting;
        private bool closing;

        public MainForm(DesktopConfig config)
        {
            this.config = config;

            Text = AppPaths.ProductName;
            Icon = Program.AppIcon;
            BackColor = Color.White;
            MinimumSize = new Size(Ui.S(900), Ui.S(600));
            StartPosition = FormStartPosition.Manual;
            ApplySavedBounds(config);

            webView = new WebView2 { Dock = DockStyle.Fill, DefaultBackgroundColor = Color.White };
            splash = new SplashPanel { Dock = DockStyle.Fill };
            splash.RetryClicked += (s, e) => StartAsync();
            splash.SettingsClicked += (s, e) => OpenSettings();

            Controls.Add(webView);
            Controls.Add(splash);
            splash.BringToFront();
        }

        public bool RestartRequested { get; private set; }

        public static string Version
        {
            get { return Assembly.GetExecutingAssembly().GetName().Version.ToString(3); }
        }

        protected override void OnHandleCreated(EventArgs e)
        {
            base.OnHandleCreated(e);

            // Extra commands live in the title-bar (system) menu, keeping the window chrome clean.
            IntPtr menu = NativeMethods.GetSystemMenu(Handle, false);
            NativeMethods.AppendMenu(menu, NativeMethods.MF_SEPARATOR, 0, null);
            NativeMethods.AppendMenu(menu, NativeMethods.MF_STRING, CmdReload, "Reload\tF5");
            NativeMethods.AppendMenu(menu, NativeMethods.MF_STRING, CmdHome, "Go to dashboard");
            NativeMethods.AppendMenu(menu, NativeMethods.MF_SEPARATOR, 0, null);
            NativeMethods.AppendMenu(menu, NativeMethods.MF_STRING, CmdSettings, "Desktop settings...");
            NativeMethods.AppendMenu(menu, NativeMethods.MF_STRING, CmdDataFolder, "Open data folder");
            NativeMethods.AppendMenu(menu, NativeMethods.MF_STRING, CmdAbout, "About " + AppPaths.ProductName);
        }

        protected override void OnShown(EventArgs e)
        {
            base.OnShown(e);
            StartAsync();
        }

        protected override void WndProc(ref Message m)
        {
            if (m.Msg == NativeMethods.WM_SYSCOMMAND)
            {
                switch (m.WParam.ToInt32() & 0xFFF0)
                {
                    case CmdReload:
                        Reload();
                        return;
                    case CmdHome:
                        GoHome();
                        return;
                    case CmdSettings:
                        OpenSettings();
                        return;
                    case CmdDataFolder:
                        Ui.OpenFolder(AppPaths.DataDir);
                        return;
                    case CmdAbout:
                        ShowAbout();
                        return;
                }
            }
            else if (m.Msg == ActivateMessage || m.Msg == SettingsMessage)
            {
                if (WindowState == FormWindowState.Minimized)
                {
                    WindowState = FormWindowState.Normal;
                }

                Activate();
                NativeMethods.SetForegroundWindow(Handle);

                if (m.Msg == SettingsMessage)
                {
                    BeginInvoke((Action)OpenSettings);
                }

                return;
            }

            base.WndProc(ref m);
        }

        protected override void OnFormClosing(FormClosingEventArgs e)
        {
            base.OnFormClosing(e);

            if (e.Cancel)
            {
                return;
            }

            closing = true;
            SaveBounds();

            // Hide first so closing feels instant while the database shuts down cleanly.
            Hide();

            if (server != null)
            {
                server.Stop();
            }
        }

        // ------------------------------------------------------------------
        // Startup
        // ------------------------------------------------------------------

        private async void StartAsync()
        {
            if (starting)
            {
                return;
            }

            starting = true;

            try
            {
                splash.ShowProgress("Starting...");
                Task serverStart = null;

                if (config.IsLocal)
                {
                    if (server != null)
                    {
                        server.Stop();
                    }

                    // Start the database and web server while the browser engine loads.
                    server = new LocalServer(config);
                    Action<string> report = ReportProgress;
                    LocalServer localServer = server;
                    serverStart = Task.Run(() => localServer.Start(report));
                }

                if (environment == null)
                {
                    environment = await CoreWebView2Environment.CreateAsync(null, AppPaths.WebViewDataDir, new CoreWebView2EnvironmentOptions());
                    await webView.EnsureCoreWebView2Async(environment);
                    ConfigureWebView(webView.CoreWebView2);
                    webView.CoreWebView2.DocumentTitleChanged += (s, e) => Text = WindowTitle(webView.CoreWebView2.DocumentTitle);
                    webView.CoreWebView2.NavigationCompleted += OnNavigationCompleted;
                    webView.CoreWebView2.ProcessFailed += OnProcessFailed;
                }

                if (serverStart != null)
                {
                    await serverStart;
                    baseUri = new Uri(server.BaseUrl + "/");
                }
                else
                {
                    baseUri = new Uri(config.NormalizedServerUrl() + "/");
                    splash.ShowProgress("Connecting to " + baseUri.Host + "...");
                }

                if (closing)
                {
                    return;
                }

                shownApp = false;
                webView.CoreWebView2.Navigate(baseUri.AbsoluteUri);
            }
            catch (LauncherException ex)
            {
                Log.Write("Startup failed: " + ex.Message);
                StopServerQuietly();

                if (!closing)
                {
                    splash.ShowError("The app could not start.", ex.Message);
                }
            }
            catch (WebView2RuntimeNotFoundException)
            {
                splash.ShowError("Microsoft Edge WebView2 Runtime is missing.", "Install it from https://go.microsoft.com/fwlink/p/?LinkId=2124703 and start the app again.");
            }
            catch (Exception ex)
            {
                Log.Write("Startup failed: " + ex);
                StopServerQuietly();

                if (!closing)
                {
                    splash.ShowError("The app could not start.", ex.Message + "\n\nDetails were written to " + Log.DesktopLog);
                }
            }
            finally
            {
                starting = false;
            }
        }

        /// <summary>Progress from the startup thread; ignored once the window is gone.</summary>
        private void ReportProgress(string message)
        {
            try
            {
                if (!closing && IsHandleCreated && !IsDisposed)
                {
                    BeginInvoke((Action)(() => splash.ShowProgress(message)));
                }
            }
            catch (InvalidOperationException)
            {
                // The window closed between the check and the call.
            }
        }

        private void StopServerQuietly()
        {
            if (server == null)
            {
                return;
            }

            try
            {
                server.Stop();
            }
            catch (Exception ex)
            {
                Log.Write("Stopping after a failed start: " + ex.Message);
            }

            server = null;
        }

        /// <summary>Applies the desktop browsing policy to the main window and every pop-up.</summary>
        private void ConfigureWebView(CoreWebView2 core)
        {
            CoreWebView2Settings settings = core.Settings;
            settings.AreDevToolsEnabled = config.DevTools;
            settings.IsStatusBarEnabled = false;
            settings.IsPasswordAutosaveEnabled = false;
            settings.IsGeneralAutofillEnabled = false;
            settings.UserAgent = settings.UserAgent + " BMPCDesktop/" + Version;

            core.NavigationStarting += OnNavigationStarting;
            core.NewWindowRequested += OnNewWindowRequested;
            core.ContextMenuRequested += OnContextMenuRequested;
        }

        private void OnNavigationStarting(object sender, CoreWebView2NavigationStartingEventArgs e)
        {
            Uri uri;

            if (!Uri.TryCreate(e.Uri, UriKind.Absolute, out uri) || IsInternal(uri))
            {
                return;
            }

            e.Cancel = true;
            OpenExternally(uri);
        }

        private void OnNavigationCompleted(object sender, CoreWebView2NavigationCompletedEventArgs e)
        {
            if (shownApp)
            {
                return;
            }

            if (!e.IsSuccess && e.WebErrorStatus != CoreWebView2WebErrorStatus.OperationCanceled)
            {
                string where = config.IsLocal ? "the local server" : baseUri.Host;
                splash.ShowError(
                    "Could not reach " + where + ".",
                    "Error: " + e.WebErrorStatus + "\n\n" + (config.IsLocal
                        ? "See the logs folder for details."
                        : "Check the network connection and the server address in Desktop settings (" + baseUri.AbsoluteUri + ")."));
                return;
            }

            if (e.IsSuccess)
            {
                shownApp = true;
                splash.Visible = false;
                webView.Focus();
            }
        }

        private async void OnNewWindowRequested(object sender, CoreWebView2NewWindowRequestedEventArgs e)
        {
            Uri uri;

            if (!Uri.TryCreate(e.Uri, UriKind.Absolute, out uri) || !IsInternal(uri))
            {
                e.Handled = true;

                if (uri != null)
                {
                    OpenExternally(uri);
                }

                return;
            }

            // Same-site links opened in a new tab (PDF reports, previews) get their own app window.
            CoreWebView2Deferral deferral = e.GetDeferral();

            try
            {
                var popup = new PopupForm();
                popup.Show();
                await popup.InitializeAsync(environment, ConfigureWebView);
                e.NewWindow = popup.WebView.CoreWebView2;
                e.Handled = true;
            }
            catch (Exception ex)
            {
                Log.Write("Pop-up window failed: " + ex.Message);
                e.Handled = true;
                webView.CoreWebView2.Navigate(uri.AbsoluteUri);
            }
            finally
            {
                deferral.Complete();
            }
        }

        private void OnContextMenuRequested(object sender, CoreWebView2ContextMenuRequestedEventArgs e)
        {
            if (config.DevTools)
            {
                return;
            }

            IList<CoreWebView2ContextMenuItem> items = e.MenuItems;

            for (int i = items.Count - 1; i >= 0; i--)
            {
                if (HiddenMenuItems.Contains(items[i].Name))
                {
                    items.RemoveAt(i);
                }
            }

            // Drop separators left at the edges or doubled up by the removals.
            for (int i = items.Count - 1; i >= 0; i--)
            {
                bool separator = items[i].Kind == CoreWebView2ContextMenuItemKind.Separator;
                bool edge = i == 0 || i == items.Count - 1;

                if (separator && (edge || items[i - 1].Kind == CoreWebView2ContextMenuItemKind.Separator))
                {
                    items.RemoveAt(i);
                }
            }
        }

        private void OnProcessFailed(object sender, CoreWebView2ProcessFailedEventArgs e)
        {
            Log.Write("WebView2 process failed: " + e.ProcessFailedKind);

            if (e.ProcessFailedKind == CoreWebView2ProcessFailedKind.BrowserProcessExited)
            {
                if (MessageBox.Show(this, "The app window stopped unexpectedly. Restart the app now?", AppPaths.ProductName, MessageBoxButtons.YesNo, MessageBoxIcon.Warning) == DialogResult.Yes)
                {
                    RestartRequested = true;
                    Close();
                }

                return;
            }

            if (e.ProcessFailedKind == CoreWebView2ProcessFailedKind.RenderProcessExited || e.ProcessFailedKind == CoreWebView2ProcessFailedKind.RenderProcessUnresponsive)
            {
                Reload();
            }
        }

        // ------------------------------------------------------------------
        // Commands
        // ------------------------------------------------------------------

        private void Reload()
        {
            if (webView.CoreWebView2 != null && shownApp)
            {
                webView.CoreWebView2.Reload();
            }
            else
            {
                StartAsync();
            }
        }

        private void GoHome()
        {
            if (webView.CoreWebView2 != null && baseUri != null)
            {
                webView.CoreWebView2.Navigate(new Uri(baseUri, "dashboard").AbsoluteUri);
            }
        }

        private void OpenSettings()
        {
            string previousMode = config.Mode;
            string previousUrl = config.NormalizedServerUrl();

            using (var form = new SettingsForm(config, config.IsLocal ? server : null, false))
            {
                if (form.ShowDialog(this) != DialogResult.OK)
                {
                    return;
                }

                bool changed = form.RestartNeeded || config.Mode != previousMode || config.NormalizedServerUrl() != previousUrl;

                if (changed)
                {
                    RestartRequested = true;
                    Close();
                }
            }
        }

        private void ShowAbout()
        {
            string mode = config.IsLocal
                ? "Offline (runs on this computer)\nLocal address: " + (server != null ? server.BaseUrl : "not running")
                : "Connected to server\nServer: " + config.NormalizedServerUrl();

            MessageBox.Show(this,
                AppPaths.ProductName + " " + Version + "\n"
                + "Barbaza Multi-Purpose Cooperative\n\n"
                + "Mode: " + mode + "\n"
                + "Copy: " + (AppPaths.IsPortable ? "Portable" : "Installed") + "\n"
                + "Data folder: " + AppPaths.DataDir + "\n"
                + "Browser engine: Microsoft Edge WebView2 " + (environment != null ? environment.BrowserVersionString : "not started"),
                "About " + AppPaths.ProductName, MessageBoxButtons.OK, MessageBoxIcon.Information);
        }

        // ------------------------------------------------------------------
        // Helpers
        // ------------------------------------------------------------------

        private bool IsInternal(Uri uri)
        {
            if (uri.Scheme == "about" || uri.Scheme == "data" || uri.Scheme == "blob")
            {
                return true;
            }

            if (baseUri == null || (uri.Scheme != Uri.UriSchemeHttp && uri.Scheme != Uri.UriSchemeHttps))
            {
                return false;
            }

            if (config.IsLocal)
            {
                return uri.Host == baseUri.Host && uri.Port == baseUri.Port;
            }

            // A hosted server may redirect between http and https.
            return string.Equals(uri.Host, baseUri.Host, StringComparison.OrdinalIgnoreCase);
        }

        private static void OpenExternally(Uri uri)
        {
            string[] allowed = { Uri.UriSchemeHttp, Uri.UriSchemeHttps, Uri.UriSchemeMailto, "tel" };

            if (!allowed.Contains(uri.Scheme))
            {
                Log.Write("Blocked navigation to " + uri.Scheme + " link.");
                return;
            }

            try
            {
                Process.Start(new ProcessStartInfo(uri.AbsoluteUri) { UseShellExecute = true });
            }
            catch (Exception ex)
            {
                Log.Write("Could not open " + uri + ": " + ex.Message);
            }
        }

        internal static string WindowTitle(string documentTitle)
        {
            if (string.IsNullOrWhiteSpace(documentTitle) || documentTitle.StartsWith("127.0.0.1") || documentTitle.StartsWith("http"))
            {
                return AppPaths.ProductName;
            }

            return documentTitle;
        }

        private void ApplySavedBounds(DesktopConfig settings)
        {
            var bounds = new Rectangle(settings.WindowX, settings.WindowY, settings.WindowWidth, settings.WindowHeight);
            bool visible = bounds.Width >= Ui.S(600) && bounds.Height >= Ui.S(400)
                && Screen.AllScreens.Any(s => s.WorkingArea.IntersectsWith(bounds));

            if (visible)
            {
                Bounds = bounds;
            }
            else
            {
                Rectangle area = Screen.PrimaryScreen.WorkingArea;
                Size size = new Size(Math.Min(Ui.S(1440), area.Width - Ui.S(80)), Math.Min(Ui.S(900), area.Height - Ui.S(60)));
                Bounds = new Rectangle(area.X + (area.Width - size.Width) / 2, area.Y + (area.Height - size.Height) / 2, size.Width, size.Height);
            }

            if (settings.WindowMaximized || !visible)
            {
                WindowState = FormWindowState.Maximized;
            }
        }

        private void SaveBounds()
        {
            try
            {
                Rectangle bounds = WindowState == FormWindowState.Normal ? Bounds : RestoreBounds;
                config.WindowX = bounds.X;
                config.WindowY = bounds.Y;
                config.WindowWidth = bounds.Width;
                config.WindowHeight = bounds.Height;
                config.WindowMaximized = WindowState == FormWindowState.Maximized;
                config.Save();
            }
            catch (Exception ex)
            {
                Log.Write("Could not save window position: " + ex.Message);
            }
        }
    }

    /// <summary>A secondary window for same-site links that ask for a new tab.</summary>
    internal sealed class PopupForm : Form
    {
        public PopupForm()
        {
            Text = AppPaths.ProductName;
            Icon = Program.AppIcon;
            BackColor = Color.White;
            Size = new Size(Ui.S(1100), Ui.S(800));
            StartPosition = FormStartPosition.CenterScreen;

            WebView = new WebView2 { Dock = DockStyle.Fill, DefaultBackgroundColor = Color.White };
            Controls.Add(WebView);
        }

        public WebView2 WebView { get; private set; }

        public async Task InitializeAsync(CoreWebView2Environment environment, Action<CoreWebView2> configure)
        {
            await WebView.EnsureCoreWebView2Async(environment);
            CoreWebView2 core = WebView.CoreWebView2;

            configure(core);
            core.WindowCloseRequested += (s, e) => Close();
            core.DocumentTitleChanged += (s, e) => Text = MainForm.WindowTitle(core.DocumentTitle);
        }
    }

    internal static class NativeMethods
    {
        public const int WM_SYSCOMMAND = 0x0112;
        public const int MF_STRING = 0x0000;
        public const int MF_SEPARATOR = 0x0800;
        public static readonly IntPtr HWND_BROADCAST = new IntPtr(0xFFFF);

        [DllImport("user32.dll", CharSet = CharSet.Unicode)]
        public static extern int RegisterWindowMessage(string message);

        [DllImport("user32.dll")]
        public static extern bool PostMessage(IntPtr hWnd, int msg, IntPtr wParam, IntPtr lParam);

        [DllImport("user32.dll")]
        public static extern IntPtr GetSystemMenu(IntPtr hWnd, bool revert);

        [DllImport("user32.dll", CharSet = CharSet.Unicode)]
        public static extern bool AppendMenu(IntPtr menu, int flags, int id, string text);

        [DllImport("user32.dll")]
        public static extern bool SetForegroundWindow(IntPtr hWnd);
    }
}
