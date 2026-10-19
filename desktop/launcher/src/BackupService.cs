using System;
using System.IO;
using System.IO.Compression;
using System.Linq;
using System.Text;

namespace Bmpc.Desktop
{
    /// <summary>
    /// Backs up and restores the offline copy: a mysqldump of the database plus
    /// every uploaded file (storage/app), packed into one .zip.
    /// </summary>
    internal static class BackupService
    {
        private const string DumpEntry = "database.sql";
        private const string ManifestEntry = "bmpc-backup.txt";
        private const string FilesPrefix = "files/";

        public static void Backup(LocalServer server, string zipPath, Action<string> status)
        {
            string work = Path.Combine(AppPaths.TempDir, "backup-" + DateTime.Now.Ticks);
            Directory.CreateDirectory(work);

            try
            {
                status("Exporting the database...");
                string dump = Path.Combine(work, DumpEntry);
                string output;
                int exit = server.MySqlTool("mysqldump",
                    "--single-transaction --routines --triggers --no-tablespaces --set-gtid-purged=OFF --default-character-set=utf8mb4 "
                    + "--result-file=" + ChildProcess.Quote(dump) + " " + server.Database,
                    600000, out output);

                if (exit != 0 || !File.Exists(dump))
                {
                    throw new LauncherException("The database export failed.\n\n" + output);
                }

                status("Packing documents and files...");
                string temp = zipPath + ".partial";

                if (File.Exists(temp))
                {
                    File.Delete(temp);
                }

                using (ZipArchive zip = ZipFile.Open(temp, ZipArchiveMode.Create))
                {
                    zip.CreateEntryFromFile(dump, DumpEntry, CompressionLevel.Optimal);

                    string files = Path.Combine(AppPaths.StorageDir, "app");

                    if (Directory.Exists(files))
                    {
                        foreach (string file in Directory.EnumerateFiles(files, "*", SearchOption.AllDirectories))
                        {
                            string relative = file.Substring(files.Length + 1).Replace('\\', '/');
                            zip.CreateEntryFromFile(file, FilesPrefix + relative, CompressionLevel.Optimal);
                        }
                    }

                    ZipArchiveEntry manifest = zip.CreateEntry(ManifestEntry);

                    using (var writer = new StreamWriter(manifest.Open(), new UTF8Encoding(false)))
                    {
                        writer.WriteLine("BMPC Project Management backup");
                        writer.WriteLine("Created: " + DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss"));
                        writer.WriteLine("App version: " + MainForm.Version);
                        writer.WriteLine("Computer: " + Environment.MachineName);
                    }
                }

                if (File.Exists(zipPath))
                {
                    File.Delete(zipPath);
                }

                File.Move(temp, zipPath);
                Log.Write("Backup written to " + zipPath);
            }
            finally
            {
                TryDelete(work);
            }
        }

        /// <summary>Replaces the database and files with a backup. The app must restart afterwards.</summary>
        public static void Restore(LocalServer server, string zipPath, Action<string> status)
        {
            string work = Path.Combine(AppPaths.TempDir, "restore-" + DateTime.Now.Ticks);
            Directory.CreateDirectory(work);

            try
            {
                status("Checking the backup...");

                using (ZipArchive zip = ZipFile.OpenRead(zipPath))
                {
                    if (zip.GetEntry(DumpEntry) == null || zip.GetEntry(ManifestEntry) == null)
                    {
                        throw new LauncherException("This file is not a BMPC Project Management backup.");
                    }

                    string root = Path.GetFullPath(work) + Path.DirectorySeparatorChar;

                    foreach (ZipArchiveEntry entry in zip.Entries.Where(e => e.Name.Length > 0))
                    {
                        string target = Path.GetFullPath(Path.Combine(work, entry.FullName.Replace('/', Path.DirectorySeparatorChar)));

                        // Refuse entries that would escape the extraction folder.
                        if (!target.StartsWith(root, StringComparison.OrdinalIgnoreCase))
                        {
                            throw new LauncherException("The backup contains an invalid path: " + entry.FullName);
                        }

                        Directory.CreateDirectory(Path.GetDirectoryName(target));
                        entry.ExtractToFile(target, true);
                    }
                }

                status("Stopping the application server...");
                server.StopWebServer();

                status("Restoring the database...");
                string output;
                string db = server.Database;
                string reset = "DROP DATABASE IF EXISTS `" + db + "`; CREATE DATABASE `" + db + "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

                if (server.MySqlTool("mysql", "-e " + ChildProcess.Quote(reset), 60000, out output) != 0)
                {
                    throw new LauncherException("The database could not be reset.\n\n" + output);
                }

                using (FileStream dump = File.OpenRead(Path.Combine(work, DumpEntry)))
                {
                    if (server.MySqlTool("mysql", "--default-character-set=utf8mb4 " + db, 1800000, out output, dump) != 0)
                    {
                        throw new LauncherException("The database could not be restored.\n\n" + output);
                    }
                }

                status("Restoring documents and files...");
                string current = Path.Combine(AppPaths.StorageDir, "app");
                string previous = Path.Combine(AppPaths.StorageDir, "app.before-restore");
                string restored = Path.Combine(work, FilesPrefix.TrimEnd('/'));

                TryDelete(previous);

                if (Directory.Exists(current))
                {
                    Directory.Move(current, previous);
                }

                if (Directory.Exists(restored))
                {
                    Directory.Move(restored, current);
                }

                Directory.CreateDirectory(Path.Combine(current, "private"));
                Directory.CreateDirectory(Path.Combine(current, "public"));

                Log.Write("Backup restored from " + zipPath);
            }
            finally
            {
                TryDelete(work);
            }
        }

        private static void TryDelete(string directory)
        {
            try
            {
                if (Directory.Exists(directory))
                {
                    Directory.Delete(directory, true);
                }
            }
            catch (Exception ex)
            {
                Log.Write("Could not remove " + directory + ": " + ex.Message);
            }
        }
    }
}
