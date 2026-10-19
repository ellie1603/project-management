using System;
using System.IO;
using System.Security.Cryptography;
using System.Text;

namespace Bmpc.Desktop
{
    /// <summary>
    /// Every folder the launcher reads from or writes to.
    ///
    /// Program files (read-only once installed) live next to the executable.
    /// Writable data lives in %LOCALAPPDATA%\BMPC Project Management for an
    /// installed copy, or in a "data" folder beside the executable when the
    /// copy is portable (a portable.flag file sits next to the executable).
    /// </summary>
    internal static class AppPaths
    {
        public const string ProductName = "BMPC Project Management";
        public const string ExeName = "BMPCProjectManagement.exe";

        /// <summary>Held by every running copy so the installer can ask the user to close it.</summary>
        public const string InstallerMutexName = "BMPCProjectManagementRunning";

        private static readonly string appDir = AppDomain.CurrentDomain.BaseDirectory.TrimEnd('\\');

        public static string AppDir { get { return appDir; } }

        public static bool IsPortable
        {
            get { return File.Exists(Path.Combine(appDir, "portable.flag")); }
        }

        public static string DataDir
        {
            get
            {
                if (IsPortable)
                {
                    return Path.Combine(appDir, "data");
                }

                return Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), ProductName);
            }
        }

        // Bundled program files.
        public static string LaravelDir { get { return Path.Combine(appDir, "app"); } }
        public static string PhpDir { get { return Path.Combine(appDir, "runtime", "php"); } }
        public static string PhpExe { get { return Path.Combine(PhpDir, "php.exe"); } }
        public static string MySqlDir { get { return Path.Combine(appDir, "runtime", "mysql"); } }
        public static string MySqlBin { get { return Path.Combine(MySqlDir, "bin"); } }
        public static string RuntimeConfigDir { get { return Path.Combine(appDir, "runtime", "config"); } }
        public static string DefaultsFile { get { return Path.Combine(appDir, "desktop.defaults.json"); } }
        public static string WebView2Bootstrapper { get { return Path.Combine(appDir, "redist", "MicrosoftEdgeWebview2Setup.exe"); } }
        public static string LogoFile { get { return Path.Combine(LaravelDir, "resources", "logo", "logo.png"); } }

        /// <summary>True when this package ships the offline server (PHP, MySQL and the Laravel app).</summary>
        public static bool HasLocalRuntime
        {
            get
            {
                return File.Exists(PhpExe)
                    && File.Exists(Path.Combine(MySqlBin, "mysqld.exe"))
                    && File.Exists(Path.Combine(LaravelDir, "artisan"));
            }
        }

        // Writable data.
        public static string ConfigFile { get { return Path.Combine(DataDir, "desktop.json"); } }
        public static string LogsDir { get { return Path.Combine(DataDir, "logs"); } }
        public static string StorageDir { get { return Path.Combine(DataDir, "storage"); } }
        public static string CacheDir { get { return Path.Combine(DataDir, "cache"); } }
        public static string MySqlDataDir { get { return Path.Combine(DataDir, "mysql"); } }
        public static string WebViewDataDir { get { return Path.Combine(DataDir, "webview"); } }
        public static string TempDir { get { return Path.Combine(DataDir, "tmp"); } }

        /// <summary>Stable per-data-folder identifier, so an installed and a portable copy can run side by side.</summary>
        public static string InstanceId
        {
            get
            {
                using (var sha = SHA1.Create())
                {
                    byte[] hash = sha.ComputeHash(Encoding.UTF8.GetBytes(DataDir.ToLowerInvariant()));
                    return BitConverter.ToString(hash, 0, 8).Replace("-", "");
                }
            }
        }

        /// <summary>Forward-slash path for my.ini, which treats backslashes as escapes.</summary>
        public static string ForIni(string path)
        {
            return path.Replace('\\', '/');
        }
    }
}
