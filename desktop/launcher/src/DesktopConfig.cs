using System;
using System.Collections.Generic;
using System.IO;
using System.Security.Cryptography;
using System.Text;
using System.Web.Script.Serialization;

namespace Bmpc.Desktop
{
    /// <summary>
    /// Launcher settings, stored as JSON in the data folder (desktop.json).
    /// </summary>
    public sealed class DesktopConfig
    {
        public const string ModeLocal = "local";
        public const string ModeServer = "server";

        public const int DefaultHttpPort = 48765;
        public const int DefaultMySqlPort = 48766;

        public DesktopConfig()
        {
            ExtraEnv = new Dictionary<string, string>();
        }

        /// <summary>"local" runs the bundled server; "server" opens a hosted BMPC server.</summary>
        public string Mode { get; set; }

        public string ServerUrl { get; set; }

        // Local mode secrets, generated on first start.
        public string AppKey { get; set; }
        public string DbPassword { get; set; }
        public bool DbProvisioned { get; set; }
        public bool Seeded { get; set; }

        public int HttpPort { get; set; }
        public int MySqlPort { get; set; }

        /// <summary>Signature of the environment the Laravel caches were built for.</summary>
        public string OptimizedFor { get; set; }

        /// <summary>yyyy-MM-dd of the last daily timeline alert check.</summary>
        public string LastAlertCheck { get; set; }

        /// <summary>Extra environment values for Laravel, e.g. GEMINI_API_KEY.</summary>
        public Dictionary<string, string> ExtraEnv { get; set; }

        public bool DevTools { get; set; }

        public int WindowX { get; set; }
        public int WindowY { get; set; }
        public int WindowWidth { get; set; }
        public int WindowHeight { get; set; }
        public bool WindowMaximized { get; set; }

        [ScriptIgnore]
        public bool IsConfigured
        {
            get
            {
                if (Mode == ModeLocal)
                {
                    return true;
                }

                return Mode == ModeServer && !string.IsNullOrWhiteSpace(ServerUrl);
            }
        }

        [ScriptIgnore]
        public bool IsLocal
        {
            get { return Mode == ModeLocal; }
        }

        public static DesktopConfig Load()
        {
            DesktopConfig config = ReadFile(AppPaths.ConfigFile);

            if (config == null)
            {
                // A packaged desktop.defaults.json can preset the mode and server URL
                // for an organisation-wide rollout.
                config = ReadFile(AppPaths.DefaultsFile) ?? new DesktopConfig();
            }

            if (config.ExtraEnv == null)
            {
                config.ExtraEnv = new Dictionary<string, string>();
            }

            if (config.HttpPort <= 0)
            {
                config.HttpPort = DefaultHttpPort;
            }

            if (config.MySqlPort <= 0)
            {
                config.MySqlPort = DefaultMySqlPort;
            }

            return config;
        }

        private static readonly object SaveGate = new object();

        public void Save()
        {
            lock (SaveGate)
            {
                SaveUnlocked();
            }
        }

        private void SaveUnlocked()
        {
            Directory.CreateDirectory(AppPaths.DataDir);

            var serializer = new JavaScriptSerializer();
            string json = JsonFormatter.Indent(serializer.Serialize(this));
            string temp = AppPaths.ConfigFile + ".tmp";

            File.WriteAllText(temp, json, new UTF8Encoding(false));

            if (File.Exists(AppPaths.ConfigFile))
            {
                File.Replace(temp, AppPaths.ConfigFile, null);
            }
            else
            {
                File.Move(temp, AppPaths.ConfigFile);
            }
        }

        /// <summary>Creates the Laravel APP_KEY and the database password on first use.</summary>
        public bool EnsureSecrets()
        {
            bool changed = false;

            if (string.IsNullOrEmpty(AppKey))
            {
                AppKey = "base64:" + Convert.ToBase64String(RandomBytes(32));
                changed = true;
            }

            if (string.IsNullOrEmpty(DbPassword))
            {
                const string alphabet = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789";
                var builder = new StringBuilder();

                foreach (byte b in RandomBytes(28))
                {
                    builder.Append(alphabet[b % alphabet.Length]);
                }

                DbPassword = builder.ToString();
                changed = true;
            }

            return changed;
        }

        public string NormalizedServerUrl()
        {
            return (ServerUrl ?? "").Trim().TrimEnd('/');
        }

        private static byte[] RandomBytes(int count)
        {
            var bytes = new byte[count];

            using (var rng = new RNGCryptoServiceProvider())
            {
                rng.GetBytes(bytes);
            }

            return bytes;
        }

        private static DesktopConfig ReadFile(string path)
        {
            if (!File.Exists(path))
            {
                return null;
            }

            try
            {
                return new JavaScriptSerializer().Deserialize<DesktopConfig>(File.ReadAllText(path));
            }
            catch (Exception ex)
            {
                Log.Write("Could not read " + path + ": " + ex.Message);
                return null;
            }
        }
    }

    /// <summary>Pretty-prints the compact JSON that JavaScriptSerializer produces.</summary>
    internal static class JsonFormatter
    {
        public static string Indent(string json)
        {
            var output = new StringBuilder();
            int depth = 0;
            bool inString = false;

            for (int i = 0; i < json.Length; i++)
            {
                char c = json[i];

                if (c == '"' && (i == 0 || json[i - 1] != '\\'))
                {
                    inString = !inString;
                }

                if (inString)
                {
                    output.Append(c);
                    continue;
                }

                switch (c)
                {
                    case '{':
                    case '[':
                        output.Append(c).Append('\n').Append(' ', ++depth * 2);
                        break;
                    case '}':
                    case ']':
                        output.Append('\n').Append(' ', --depth * 2).Append(c);
                        break;
                    case ',':
                        output.Append(c).Append('\n').Append(' ', depth * 2);
                        break;
                    case ':':
                        output.Append(": ");
                        break;
                    default:
                        output.Append(c);
                        break;
                }
            }

            return output.ToString();
        }
    }
}
