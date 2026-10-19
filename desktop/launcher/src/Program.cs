using System;
using System.Diagnostics;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Threading;
using System.Windows.Forms;
using Microsoft.Web.WebView2.Core;

namespace Bmpc.Desktop
{
    internal static class Program
    {
        public static Icon AppIcon { get; private set; }

        [STAThread]
        private static int Main(string[] args)
        {
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);
            Application.SetUnhandledExceptionMode(UnhandledExceptionMode.CatchException);
            Application.ThreadException += (s, e) => ReportFatal(e.Exception);
            AppDomain.CurrentDomain.UnhandledException += (s, e) => ReportFatal(e.ExceptionObject as Exception);

            AppIcon = Icon.ExtractAssociatedIcon(Application.ExecutablePath);
            bool openSettings = args.Any(a => a == "--settings" || a == "--setup");

            try
            {
                Directory.CreateDirectory(AppPaths.DataDir);
            }
            catch (Exception ex)
            {
                MessageBox.Show(
                    "The data folder could not be created:\n" + AppPaths.DataDir + "\n\n" + ex.Message
                    + (AppPaths.IsPortable ? "\n\nMove the portable folder somewhere you can write to, such as Documents or a USB drive." : ""),
                    AppPaths.ProductName, MessageBoxButtons.OK, MessageBoxIcon.Error);
                return 1;
            }

            bool restart;
            bool createdNew;

            // The installer watches this one to ask the user to close the app first.
            using (new Mutex(false, AppPaths.InstallerMutexName))
            using (var instance = new Mutex(true, "BMPCProjectManagement-" + AppPaths.InstanceId, out createdNew))
            {
                if (!createdNew)
                {
                    // Already running from this data folder: bring that window forward instead.
                    int message = openSettings ? MainForm.SettingsMessage : MainForm.ActivateMessage;
                    NativeMethods.PostMessage(NativeMethods.HWND_BROADCAST, message, IntPtr.Zero, IntPtr.Zero);
                    return 0;
                }

                Log.Write("Launcher " + MainForm.Version + " starting (" + (AppPaths.IsPortable ? "portable" : "installed") + ").");

                if (!EnsureWebView2())
                {
                    return 1;
                }

                DesktopConfig config = DesktopConfig.Load();
                bool needsSetup = !config.IsConfigured || (config.IsLocal && !AppPaths.HasLocalRuntime);

                if (needsSetup || openSettings)
                {
                    using (var setup = new SettingsForm(config, null, needsSetup))
                    {
                        if (setup.ShowDialog() != DialogResult.OK || !config.IsConfigured)
                        {
                            return 0;
                        }
                    }
                }

                using (var main = new MainForm(config))
                {
                    Application.Run(main);
                    restart = main.RestartRequested;
                }

                instance.ReleaseMutex();
            }

            if (restart)
            {
                Process.Start(Application.ExecutablePath);
            }

            return 0;
        }

        /// <summary>Makes sure the Edge WebView2 Runtime is present, offering to install it if not.</summary>
        private static bool EnsureWebView2()
        {
            if (WebView2Version() != null)
            {
                return true;
            }

            string bootstrapper = AppPaths.WebView2Bootstrapper;

            if (File.Exists(bootstrapper))
            {
                DialogResult answer = MessageBox.Show(
                    AppPaths.ProductName + " needs the Microsoft Edge WebView2 Runtime, which is not installed on this computer.\n\nInstall it now? An internet connection is required.",
                    AppPaths.ProductName, MessageBoxButtons.YesNo, MessageBoxIcon.Information);

                if (answer == DialogResult.Yes)
                {
                    try
                    {
                        using (Process process = Process.Start(bootstrapper, "/install"))
                        {
                            process.WaitForExit();
                        }
                    }
                    catch (Exception ex)
                    {
                        Log.Write("WebView2 install failed: " + ex.Message);
                    }

                    if (WebView2Version() != null)
                    {
                        return true;
                    }
                }
            }

            MessageBox.Show(
                "The Microsoft Edge WebView2 Runtime is required.\n\nDownload it from:\nhttps://developer.microsoft.com/microsoft-edge/webview2/",
                AppPaths.ProductName, MessageBoxButtons.OK, MessageBoxIcon.Warning);

            return false;
        }

        private static string WebView2Version()
        {
            try
            {
                return CoreWebView2Environment.GetAvailableBrowserVersionString();
            }
            catch (WebView2RuntimeNotFoundException)
            {
                return null;
            }
            catch (Exception ex)
            {
                Log.Write("WebView2 check failed: " + ex.Message);
                return null;
            }
        }

        private static void ReportFatal(Exception ex)
        {
            Log.Write("Unhandled error: " + ex);
            MessageBox.Show(
                "Something went wrong.\n\n" + (ex != null ? ex.Message : "Unknown error") + "\n\nDetails were written to " + Log.DesktopLog,
                AppPaths.ProductName, MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }
}
