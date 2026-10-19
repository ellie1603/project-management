using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Net;
using System.Net.Sockets;
using System.Security.Cryptography;
using System.Text;
using System.Threading;

namespace Bmpc.Desktop
{
    /// <summary>
    /// Runs the bundled offline server: a private MySQL 8 instance plus PHP's
    /// built-in web server hosting the Laravel app, both bound to 127.0.0.1.
    /// </summary>
    internal sealed class LocalServer : IDisposable
    {
        private const string DatabaseName = "bmpc";
        private const string DbUser = "root";

        private readonly DesktopConfig config;
        private readonly object stopGate = new object();
        private Process mysqld;
        private Process php;
        private Timer scheduler;
        private int schedulerBusy;
        private bool stopped;

        public LocalServer(DesktopConfig config)
        {
            this.config = config;
        }

        public string BaseUrl
        {
            get { return "http://127.0.0.1:" + config.HttpPort; }
        }

        private static string ArtisanLog { get { return Path.Combine(AppPaths.LogsDir, "artisan.log"); } }
        private static string ServerLog { get { return Path.Combine(AppPaths.LogsDir, "php-server.log"); } }
        private static string MySqlLog { get { return Path.Combine(AppPaths.LogsDir, "mysql.log"); } }
        private static string MySqlErrorLog { get { return Path.Combine(AppPaths.LogsDir, "mysql-error.log"); } }
        private static string MySqlIni { get { return Path.Combine(AppPaths.DataDir, "my.ini"); } }
        private static string MySqlClientIni { get { return Path.Combine(AppPaths.DataDir, "mysql-client.ini"); } }

        /// <summary>Starts everything; blocks until the app answers HTTP requests. Call off the UI thread.</summary>
        public void Start(Action<string> status)
        {
            Log.Write("Starting local server from " + AppPaths.AppDir + ", data in " + AppPaths.DataDir);

            EnsureLayout();

            if (config.EnsureSecrets())
            {
                config.Save();
            }

            ChoosePorts();

            status("Starting the database...");
            StartMySql(status);

            ThrowIfStopped();
            status("Preparing the application...");
            PrepareLaravel(status);

            ThrowIfStopped();
            status("Starting the application server...");
            StartPhp();
            WaitForHttp(BaseUrl + "/up", TimeSpan.FromSeconds(45));

            StartScheduler();

            Log.Write("Local server ready at " + BaseUrl);
        }

        public void Stop()
        {
            lock (stopGate)
            {
                if (stopped)
                {
                    return;
                }

                stopped = true;
            }

            if (scheduler != null)
            {
                scheduler.Dispose();
            }

            ChildProcess.TryKill(php);
            StopMySql();
            Log.Write("Local server stopped.");
        }

        public void Dispose()
        {
            Stop();
        }

        /// <summary>Stop() may run while Start() is still going (window closed during startup).</summary>
        private void ThrowIfStopped()
        {
            lock (stopGate)
            {
                if (stopped)
                {
                    throw new LauncherException("Startup was cancelled.");
                }
            }
        }

        /// <summary>Runs "php artisan ..." against the local database.</summary>
        public int Artisan(string arguments, int timeoutMs, out string output)
        {
            return ChildProcess.Run(AppPaths.PhpExe, PhpBaseArguments() + " artisan " + arguments + " --no-interaction", AppPaths.LaravelDir, LaravelEnvironment(), ArtisanLog, timeoutMs, out output);
        }

        /// <summary>Runs a bundled MySQL client tool (mysqldump, mysql) as the app's database user.</summary>
        public int MySqlTool(string tool, string arguments, int timeoutMs, out string output, Stream stdin = null)
        {
            WriteClientIni();
            string args = "--defaults-extra-file=" + ChildProcess.Quote(MySqlClientIni) + " " + arguments;

            return ChildProcess.Run(Path.Combine(AppPaths.MySqlBin, tool + ".exe"), args, AppPaths.DataDir, null, MySqlLog, timeoutMs, out output, stdin);
        }

        public string Database { get { return DatabaseName; } }

        /// <summary>Stops only the web server, e.g. while a backup is being restored.</summary>
        public void StopWebServer()
        {
            if (scheduler != null)
            {
                scheduler.Dispose();
                scheduler = null;
            }

            ChildProcess.TryKill(php);
        }

        // ------------------------------------------------------------------
        // Layout and ports
        // ------------------------------------------------------------------

        private static void EnsureLayout()
        {
            string[] directories =
            {
                AppPaths.LogsDir,
                AppPaths.CacheDir,
                AppPaths.TempDir,
                Path.Combine(AppPaths.CacheDir, "opcache"),
                Path.Combine(AppPaths.StorageDir, "app", "private"),
                Path.Combine(AppPaths.StorageDir, "app", "public"),
                Path.Combine(AppPaths.StorageDir, "framework", "cache", "data"),
                Path.Combine(AppPaths.StorageDir, "framework", "sessions"),
                Path.Combine(AppPaths.StorageDir, "framework", "views"),
                Path.Combine(AppPaths.StorageDir, "logs"),
            };

            foreach (string directory in directories)
            {
                Directory.CreateDirectory(directory);
            }
        }

        private void ChoosePorts()
        {
            int http = PickPort(config.HttpPort, DesktopConfig.DefaultHttpPort);
            int mysql = PickPort(config.MySqlPort, DesktopConfig.DefaultMySqlPort, http);

            if (http != config.HttpPort || mysql != config.MySqlPort)
            {
                Log.Write("Ports changed: http " + config.HttpPort + " -> " + http + ", mysql " + config.MySqlPort + " -> " + mysql);
                config.HttpPort = http;
                config.MySqlPort = mysql;
                config.Save();
            }
        }

        private static int PickPort(int preferred, int fallback, int exclude = 0)
        {
            foreach (int candidate in new[] { preferred, fallback })
            {
                if (candidate > 0 && candidate != exclude && IsPortFree(candidate))
                {
                    return candidate;
                }
            }

            var listener = new TcpListener(IPAddress.Loopback, 0);
            listener.Start();
            int port = ((IPEndPoint)listener.LocalEndpoint).Port;
            listener.Stop();

            return port;
        }

        private static bool IsPortFree(int port)
        {
            try
            {
                var listener = new TcpListener(IPAddress.Loopback, port);
                listener.Start();
                listener.Stop();
                return true;
            }
            catch (SocketException)
            {
                return false;
            }
        }

        // ------------------------------------------------------------------
        // MySQL
        // ------------------------------------------------------------------

        private void StartMySql(Action<string> status)
        {
            WriteServerIni();

            if (!File.Exists(Path.Combine(AppPaths.MySqlDataDir, "mysql.ibd")))
            {
                status("Creating the database (first start only)...");
                InitializeDataDirectory();
                config.DbProvisioned = false;
                config.Seeded = false;
                config.OptimizedFor = null;
                config.Save();
            }

            string initFile = null;
            string args = "--defaults-file=" + ChildProcess.Quote(MySqlIni);

            if (!config.DbProvisioned)
            {
                initFile = WriteInitFile();
                args += " --init-file=" + ChildProcess.Quote(initFile);
            }

            mysqld = ChildProcess.Start(Path.Combine(AppPaths.MySqlBin, "mysqld.exe"), args, AppPaths.MySqlDir, null, MySqlLog);

            try
            {
                WaitForPort(config.MySqlPort, mysqld, TimeSpan.FromSeconds(120));
                WaitForLogin(TimeSpan.FromSeconds(30));
            }
            finally
            {
                if (initFile != null && File.Exists(initFile))
                {
                    File.Delete(initFile);
                }
            }

            if (!config.DbProvisioned)
            {
                config.DbProvisioned = true;
                config.Save();
            }
        }

        private void InitializeDataDirectory()
        {
            if (Directory.Exists(AppPaths.MySqlDataDir))
            {
                // Only a failed earlier initialisation can leave a data folder without
                // the system tablespace. Never delete one that holds the app database.
                if (Directory.Exists(Path.Combine(AppPaths.MySqlDataDir, DatabaseName)))
                {
                    throw new LauncherException("The database folder looks damaged: " + AppPaths.MySqlDataDir + ". Restore a backup or contact support.");
                }

                Directory.Delete(AppPaths.MySqlDataDir, true);
            }

            string output;
            int exit = ChildProcess.Run(
                Path.Combine(AppPaths.MySqlBin, "mysqld.exe"),
                "--defaults-file=" + ChildProcess.Quote(MySqlIni) + " --initialize-insecure",
                AppPaths.MySqlDir, null, MySqlLog, 300000, out output);

            if (exit != 0 || !File.Exists(Path.Combine(AppPaths.MySqlDataDir, "mysql.ibd")))
            {
                throw new LauncherException("The database could not be created (exit " + exit + "). See " + MySqlErrorLog + "\n\n" + Tail(MySqlErrorLog, 15));
            }
        }

        private void WriteServerIni()
        {
            var ini = new StringBuilder();
            ini.AppendLine("# Generated by the BMPC desktop launcher on every start. Do not edit.");
            ini.AppendLine("[mysqld]");
            ini.AppendLine("basedir=\"" + AppPaths.ForIni(AppPaths.MySqlDir) + "\"");
            ini.AppendLine("datadir=\"" + AppPaths.ForIni(AppPaths.MySqlDataDir) + "\"");
            ini.AppendLine("port=" + config.MySqlPort);
            ini.AppendLine("bind-address=127.0.0.1");
            ini.AppendLine("mysqlx=OFF");
            ini.AppendLine("skip-name-resolve");
            ini.AppendLine("disable-log-bin");
            ini.AppendLine("log-error=\"" + AppPaths.ForIni(MySqlErrorLog) + "\"");
            ini.AppendLine("character-set-server=utf8mb4");
            ini.AppendLine("collation-server=utf8mb4_unicode_ci");
            ini.AppendLine("innodb_buffer_pool_size=128M");
            ini.AppendLine("innodb_log_buffer_size=16M");
            ini.AppendLine("max_connections=50");
            ini.AppendLine("max_allowed_packet=64M");
            ini.AppendLine("secure-file-priv=\"" + AppPaths.ForIni(AppPaths.TempDir) + "\"");
            ini.AppendLine("performance_schema=OFF");

            File.WriteAllText(MySqlIni, ini.ToString(), new UTF8Encoding(false));
        }

        private string WriteInitFile()
        {
            string password = config.DbPassword.Replace("'", "''");
            string path = Path.Combine(AppPaths.DataDir, "mysql-init.sql");
            var sql = new StringBuilder();

            // --initialize-insecure leaves root@localhost without a password and
            // skip-name-resolve means TCP clients arrive as 127.0.0.1.
            sql.AppendLine("ALTER USER 'root'@'localhost' IDENTIFIED BY '" + password + "';");
            sql.AppendLine("CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY '" + password + "';");
            sql.AppendLine("ALTER USER 'root'@'127.0.0.1' IDENTIFIED BY '" + password + "';");
            sql.AppendLine("GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;");
            sql.AppendLine("CREATE DATABASE IF NOT EXISTS `" + DatabaseName + "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

            File.WriteAllText(path, sql.ToString(), new UTF8Encoding(false));

            return path;
        }

        private void WriteClientIni()
        {
            var ini = new StringBuilder();
            ini.AppendLine("[client]");
            ini.AppendLine("user=" + DbUser);
            ini.AppendLine("password=\"" + config.DbPassword + "\"");
            ini.AppendLine("host=127.0.0.1");
            ini.AppendLine("port=" + config.MySqlPort);
            ini.AppendLine("protocol=TCP");
            ini.AppendLine("default-character-set=utf8mb4");

            File.WriteAllText(MySqlClientIni, ini.ToString(), new UTF8Encoding(false));
        }

        /// <summary>The port can open slightly before accounts are ready; confirm a real login works.</summary>
        private void WaitForLogin(TimeSpan timeout)
        {
            DateTime deadline = DateTime.UtcNow + timeout;
            string output = "";

            while (DateTime.UtcNow < deadline)
            {
                // "ping" succeeds even when access is denied; "status" needs a real login.
                if (MySqlTool("mysqladmin", "status", 10000, out output) == 0)
                {
                    return;
                }

                Thread.Sleep(500);
            }

            throw new LauncherException("The database started but refused the app's login.\n\n" + output);
        }

        private void StopMySql()
        {
            if (mysqld == null || mysqld.HasExited)
            {
                return;
            }

            try
            {
                string output;
                MySqlTool("mysqladmin", "shutdown", 20000, out output);
                mysqld.WaitForExit(20000);
            }
            catch (Exception ex)
            {
                Log.Write("Graceful MySQL shutdown failed: " + ex.Message);
            }

            ChildProcess.TryKill(mysqld);
        }

        // ------------------------------------------------------------------
        // Laravel
        // ------------------------------------------------------------------

        private void PrepareLaravel(Action<string> status)
        {
            string output;
            string signature = EnvironmentSignature();

            if (config.OptimizedFor != signature && CanRelocateCaches)
            {
                // A new app version or changed ports: drop caches built for the old environment.
                Artisan("optimize:clear", 120000, out output);
            }

            status("Updating the database structure...");

            if (Artisan("migrate --force", 600000, out output) != 0)
            {
                throw new LauncherException("The database could not be updated. See " + ArtisanLog + "\n\n" + TailText(output, 20));
            }

            if (!config.Seeded)
            {
                status("Creating the default accounts...");

                if (Artisan("db:seed --force", 300000, out output) != 0)
                {
                    throw new LauncherException("The default accounts could not be created. See " + ArtisanLog + "\n\n" + TailText(output, 20));
                }

                config.Seeded = true;
                config.Save();
            }

            if (config.OptimizedFor != signature && CanRelocateCaches)
            {
                status("Optimising the application (after install or update)...");

                if (Artisan("optimize", 300000, out output) == 0)
                {
                    config.OptimizedFor = signature;
                    config.Save();
                }
                else
                {
                    // The app still works uncached, only slower.
                    Artisan("optimize:clear", 120000, out output);
                }
            }

            RunDailyAlertsIfDue();
        }

        private void StartPhp()
        {
            string args = PhpBaseArguments()
                + " -S 127.0.0.1:" + config.HttpPort
                + " -t " + ChildProcess.Quote(Path.Combine(AppPaths.LaravelDir, "public"))
                + " " + ChildProcess.Quote(Path.Combine(AppPaths.RuntimeConfigDir, "router.php"));

            php = ChildProcess.Start(AppPaths.PhpExe, args, Path.Combine(AppPaths.LaravelDir, "public"), LaravelEnvironment(), ServerLog);
        }

        private static string PhpBaseArguments()
        {
            return "-c " + ChildProcess.Quote(Path.Combine(AppPaths.RuntimeConfigDir, "php.ini"))
                + " " + ChildProcess.Quote("-dextension_dir=" + Path.Combine(AppPaths.PhpDir, "ext"))
                + " " + ChildProcess.Quote("-dopcache.file_cache=" + Path.Combine(AppPaths.CacheDir, "opcache"))
                + " " + ChildProcess.Quote("-dcurl.cainfo=" + Path.Combine(AppPaths.RuntimeConfigDir, "cacert.pem"))
                + " " + ChildProcess.Quote("-dopenssl.cafile=" + Path.Combine(AppPaths.RuntimeConfigDir, "cacert.pem"))
                + " " + ChildProcess.Quote("-dsys_temp_dir=" + AppPaths.TempDir)
                + " " + ChildProcess.Quote("-dupload_tmp_dir=" + AppPaths.TempDir)
                + " " + ChildProcess.Quote("-derror_log=" + Path.Combine(AppPaths.LogsDir, "php-error.log"));
        }

        /// <summary>
        /// The whole Laravel configuration, passed as environment variables so the
        /// read-only app folder needs no .env file. Writable paths point into the data folder.
        /// </summary>
        private Dictionary<string, string> LaravelEnvironment()
        {
            var env = new Dictionary<string, string>
            {
                { "APP_NAME", AppPaths.ProductName },
                { "APP_ENV", "production" },
                { "APP_DEBUG", "false" },
                { "APP_KEY", config.AppKey },
                { "APP_URL", BaseUrl },
                { "LOG_CHANNEL", "daily" },
                { "LOG_LEVEL", "info" },
                { "DB_CONNECTION", "mysql" },
                { "DB_HOST", "127.0.0.1" },
                { "DB_PORT", config.MySqlPort.ToString() },
                { "DB_DATABASE", DatabaseName },
                { "DB_USERNAME", DbUser },
                { "DB_PASSWORD", config.DbPassword },
                { "DB_URL", "" },
                { "SESSION_DRIVER", "database" },
                { "SESSION_SECURE_COOKIE", "false" },
                { "CACHE_STORE", "database" },
                { "QUEUE_CONNECTION", "sync" },
                { "MAIL_MAILER", "log" },
                { "BROADCAST_CONNECTION", "log" },
                { "FILESYSTEM_DISK", "local" },
                { "LARAVEL_STORAGE_PATH", AppPaths.StorageDir },
                { "TMP", AppPaths.TempDir },
                { "TEMP", AppPaths.TempDir },
                { "BMPC_DESKTOP", "1" },
            };

            if (CanRelocateCaches)
            {
                env["APP_SERVICES_CACHE"] = CachePath("services.php");
                env["APP_PACKAGES_CACHE"] = CachePath("packages.php");
                env["APP_CONFIG_CACHE"] = CachePath("config.php");
                env["APP_ROUTES_CACHE"] = CachePath("routes-v7.php");
                env["APP_EVENTS_CACHE"] = CachePath("events.php");
            }

            foreach (KeyValuePair<string, string> pair in config.ExtraEnv)
            {
                env[pair.Key] = pair.Value;
            }

            return env;
        }

        /// <summary>
        /// Laravel treats APP_*_CACHE values as absolute only when they start with a
        /// slash, so on Windows they are given relative to the app folder. That needs
        /// the app and data folders on the same drive; otherwise the caches built into
        /// the package (bootstrap\cache) are used as they are and nothing is optimised.
        /// </summary>
        private static bool CanRelocateCaches
        {
            get
            {
                return string.Equals(Path.GetPathRoot(Path.GetFullPath(AppPaths.LaravelDir)), Path.GetPathRoot(Path.GetFullPath(AppPaths.CacheDir)), StringComparison.OrdinalIgnoreCase);
            }
        }

        private static string CachePath(string file)
        {
            string[] from = Path.GetFullPath(AppPaths.LaravelDir).TrimEnd('\\').Split('\\');
            string[] to = Path.GetFullPath(Path.Combine(AppPaths.CacheDir, file)).Split('\\');
            int common = 0;

            while (common < from.Length && common < to.Length - 1 && string.Equals(from[common], to[common], StringComparison.OrdinalIgnoreCase))
            {
                common++;
            }

            var parts = Enumerable.Repeat("..", from.Length - common).Concat(to.Skip(common));

            return string.Join("\\", parts);
        }

        private string EnvironmentSignature()
        {
            var text = new StringBuilder();
            string buildFile = Path.Combine(AppPaths.LaravelDir, ".desktop-build");
            text.AppendLine(File.Exists(buildFile) ? File.ReadAllText(buildFile) : AppPaths.AppDir);

            foreach (KeyValuePair<string, string> pair in LaravelEnvironment().OrderBy(p => p.Key, StringComparer.Ordinal))
            {
                text.Append(pair.Key).Append('=').AppendLine(pair.Value);
            }

            using (var sha = SHA256.Create())
            {
                return BitConverter.ToString(sha.ComputeHash(Encoding.UTF8.GetBytes(text.ToString()))).Replace("-", "");
            }
        }

        // ------------------------------------------------------------------
        // Scheduler
        // ------------------------------------------------------------------

        /// <summary>
        /// Stands in for cron while the app is open: runs schedule:run every minute,
        /// and catches up the 06:00 timeline alert check on days it was missed.
        /// </summary>
        private void StartScheduler()
        {
            int delay = (60 - DateTime.Now.Second) * 1000;
            scheduler = new Timer(_ => SchedulerTick(), null, delay, 60000);
        }

        private void SchedulerTick()
        {
            if (Interlocked.Exchange(ref schedulerBusy, 1) == 1)
            {
                return;
            }

            try
            {
                DateTime now = DateTime.Now;
                string output;
                Artisan("schedule:run", 300000, out output);

                if (now.Hour == 6 && now.Minute == 0)
                {
                    // schedule:run just fired the daily projects:check-alerts.
                    MarkAlertsChecked(now);
                }
                else
                {
                    RunDailyAlertsIfDue();
                }
            }
            catch (Exception ex)
            {
                Log.Write("Scheduler tick failed: " + ex.Message);
            }
            finally
            {
                Interlocked.Exchange(ref schedulerBusy, 0);
            }
        }

        private void RunDailyAlertsIfDue()
        {
            DateTime now = DateTime.Now;
            string today = now.ToString("yyyy-MM-dd");

            if (now.Hour < 6 || config.LastAlertCheck == today)
            {
                return;
            }

            string output;

            if (Artisan("projects:check-alerts", 300000, out output) == 0)
            {
                MarkAlertsChecked(now);
            }
        }

        private void MarkAlertsChecked(DateTime when)
        {
            config.LastAlertCheck = when.ToString("yyyy-MM-dd");
            config.Save();
        }

        // ------------------------------------------------------------------
        // Waiting helpers
        // ------------------------------------------------------------------

        private static void WaitForPort(int port, Process owner, TimeSpan timeout)
        {
            DateTime deadline = DateTime.UtcNow + timeout;

            while (DateTime.UtcNow < deadline)
            {
                if (owner.HasExited)
                {
                    throw new LauncherException("The database stopped while starting (exit " + owner.ExitCode + ").\n\n" + Tail(MySqlErrorLog, 15));
                }

                try
                {
                    using (var client = new TcpClient())
                    {
                        IAsyncResult result = client.BeginConnect(IPAddress.Loopback, port, null, null);

                        if (result.AsyncWaitHandle.WaitOne(500) && client.Connected)
                        {
                            client.EndConnect(result);
                            return;
                        }
                    }
                }
                catch (SocketException)
                {
                    // Not listening yet.
                }

                Thread.Sleep(300);
            }

            throw new LauncherException("The database did not start in time.\n\n" + Tail(MySqlErrorLog, 15));
        }

        private void WaitForHttp(string url, TimeSpan timeout)
        {
            DateTime deadline = DateTime.UtcNow + timeout;
            string lastError = "";

            while (DateTime.UtcNow < deadline)
            {
                if (php.HasExited)
                {
                    throw new LauncherException("The application server stopped while starting.\n\n" + Tail(ServerLog, 15));
                }

                try
                {
                    var request = (HttpWebRequest)WebRequest.Create(url);
                    request.Timeout = 5000;
                    request.Proxy = null;

                    using (var response = (HttpWebResponse)request.GetResponse())
                    {
                        if (response.StatusCode == HttpStatusCode.OK)
                        {
                            return;
                        }
                    }
                }
                catch (WebException ex)
                {
                    lastError = ex.Message;
                }

                Thread.Sleep(300);
            }

            throw new LauncherException("The application server did not respond: " + lastError + "\n\n" + Tail(ServerLog, 15));
        }

        private static string Tail(string path, int lines)
        {
            try
            {
                if (!File.Exists(path))
                {
                    return "";
                }

                using (var stream = new FileStream(path, FileMode.Open, FileAccess.Read, FileShare.ReadWrite | FileShare.Delete))
                using (var reader = new StreamReader(stream))
                {
                    return TailText(reader.ReadToEnd(), lines);
                }
            }
            catch (IOException)
            {
                return "";
            }
        }

        private static string TailText(string text, int lines)
        {
            string[] all = (text ?? "").Replace("\r", "").Split('\n').Where(l => l.Trim().Length > 0).ToArray();

            return string.Join("\n", all.Skip(Math.Max(0, all.Length - lines)));
        }
    }

    /// <summary>A startup failure with a message meant for the user.</summary>
    internal sealed class LauncherException : Exception
    {
        public LauncherException(string message) : base(message)
        {
        }
    }
}
