using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Runtime.InteropServices;
using System.Text;

namespace Bmpc.Desktop
{
    /// <summary>
    /// Starts hidden helper processes (PHP, MySQL) and ties their lifetime to the
    /// launcher through a Windows job object, so they never outlive the app,
    /// even if the launcher crashes.
    /// </summary>
    internal static class ChildProcess
    {
        private static readonly IntPtr Job = CreateKillOnCloseJob();

        public static Process Start(string file, string arguments, string workingDirectory, IDictionary<string, string> env, string logFile)
        {
            ProcessStartInfo info = CreateStartInfo(file, arguments, workingDirectory, env);
            var process = new Process { StartInfo = info, EnableRaisingEvents = true };
            var writer = new LogWriter(logFile);

            process.OutputDataReceived += (s, e) => writer.WriteLine(e.Data);
            process.ErrorDataReceived += (s, e) => writer.WriteLine(e.Data);
            process.Exited += (s, e) => writer.Dispose();

            process.Start();
            Track(process);
            process.BeginOutputReadLine();
            process.BeginErrorReadLine();

            return process;
        }

        /// <summary>Runs a process to completion and returns its exit code; output is appended to the log.</summary>
        public static int Run(string file, string arguments, string workingDirectory, IDictionary<string, string> env, string logFile, int timeoutMs, out string output, Stream stdin = null)
        {
            ProcessStartInfo info = CreateStartInfo(file, arguments, workingDirectory, env);
            info.RedirectStandardInput = stdin != null;

            var buffer = new StringBuilder();
            var gate = new object();

            using (var process = new Process { StartInfo = info })
            {
                DataReceivedEventHandler collect = (s, e) =>
                {
                    if (e.Data != null)
                    {
                        lock (gate)
                        {
                            buffer.AppendLine(e.Data);
                        }
                    }
                };

                process.OutputDataReceived += collect;
                process.ErrorDataReceived += collect;
                process.Start();
                Track(process);
                process.BeginOutputReadLine();
                process.BeginErrorReadLine();

                if (stdin != null)
                {
                    stdin.CopyTo(process.StandardInput.BaseStream);
                    process.StandardInput.Close();
                }

                if (!process.WaitForExit(timeoutMs))
                {
                    TryKill(process);
                    output = buffer + "\n[timed out after " + (timeoutMs / 1000) + "s]";
                    Log.Append(logFile, "> " + Path.GetFileName(file) + " " + Redact(arguments) + "\n" + output);
                    return -1;
                }

                process.WaitForExit();

                lock (gate)
                {
                    output = buffer.ToString();
                }

                Log.Append(logFile, "> " + Path.GetFileName(file) + " " + Redact(arguments) + " (exit " + process.ExitCode + ")\n" + output);

                return process.ExitCode;
            }
        }

        public static void TryKill(Process process)
        {
            try
            {
                if (process != null && !process.HasExited)
                {
                    process.Kill();
                    process.WaitForExit(5000);
                }
            }
            catch (Exception)
            {
                // Already gone.
            }
        }

        public static string Quote(string value)
        {
            // A trailing backslash would escape the closing quote.
            return "\"" + value.TrimEnd('\\') + "\"";
        }

        private static string Redact(string arguments)
        {
            return arguments.Length > 400 ? arguments.Substring(0, 400) + "..." : arguments;
        }

        private static ProcessStartInfo CreateStartInfo(string file, string arguments, string workingDirectory, IDictionary<string, string> env)
        {
            var info = new ProcessStartInfo(file, arguments)
            {
                WorkingDirectory = workingDirectory,
                UseShellExecute = false,
                CreateNoWindow = true,
                WindowStyle = ProcessWindowStyle.Hidden,
                RedirectStandardOutput = true,
                RedirectStandardError = true,
                StandardOutputEncoding = Encoding.UTF8,
                StandardErrorEncoding = Encoding.UTF8,
            };

            if (env != null)
            {
                foreach (KeyValuePair<string, string> pair in env)
                {
                    info.EnvironmentVariables[pair.Key] = pair.Value;
                }
            }

            return info;
        }

        private static void Track(Process process)
        {
            if (Job != IntPtr.Zero)
            {
                AssignProcessToJobObject(Job, process.Handle);
            }
        }

        private static IntPtr CreateKillOnCloseJob()
        {
            IntPtr job = CreateJobObject(IntPtr.Zero, null);

            if (job == IntPtr.Zero)
            {
                return IntPtr.Zero;
            }

            var info = new JOBOBJECT_EXTENDED_LIMIT_INFORMATION();
            info.BasicLimitInformation.LimitFlags = JOB_OBJECT_LIMIT_KILL_ON_JOB_CLOSE;

            int length = Marshal.SizeOf(typeof(JOBOBJECT_EXTENDED_LIMIT_INFORMATION));
            IntPtr pointer = Marshal.AllocHGlobal(length);

            try
            {
                Marshal.StructureToPtr(info, pointer, false);
                SetInformationJobObject(job, JobObjectExtendedLimitInformation, pointer, (uint)length);
            }
            finally
            {
                Marshal.FreeHGlobal(pointer);
            }

            return job;
        }

        /// <summary>Appends child output to a log file, starting over once it grows past 5 MB.</summary>
        private sealed class LogWriter : IDisposable
        {
            private readonly object gate = new object();
            private StreamWriter writer;

            public LogWriter(string path)
            {
                try
                {
                    Directory.CreateDirectory(Path.GetDirectoryName(path));
                    bool append = !File.Exists(path) || new FileInfo(path).Length < 5 * 1024 * 1024;
                    writer = new StreamWriter(path, append, new UTF8Encoding(false)) { AutoFlush = true };
                }
                catch (IOException)
                {
                    writer = null;
                }
            }

            public void WriteLine(string line)
            {
                if (line == null)
                {
                    return;
                }

                lock (gate)
                {
                    if (writer != null)
                    {
                        writer.WriteLine(line);
                    }
                }
            }

            public void Dispose()
            {
                lock (gate)
                {
                    if (writer != null)
                    {
                        writer.Dispose();
                        writer = null;
                    }
                }
            }
        }

        private const uint JOB_OBJECT_LIMIT_KILL_ON_JOB_CLOSE = 0x2000;
        private const int JobObjectExtendedLimitInformation = 9;

        [StructLayout(LayoutKind.Sequential)]
        private struct JOBOBJECT_BASIC_LIMIT_INFORMATION
        {
            public long PerProcessUserTimeLimit;
            public long PerJobUserTimeLimit;
            public uint LimitFlags;
            public UIntPtr MinimumWorkingSetSize;
            public UIntPtr MaximumWorkingSetSize;
            public uint ActiveProcessLimit;
            public UIntPtr Affinity;
            public uint PriorityClass;
            public uint SchedulingClass;
        }

        [StructLayout(LayoutKind.Sequential)]
        private struct IO_COUNTERS
        {
            public ulong ReadOperationCount;
            public ulong WriteOperationCount;
            public ulong OtherOperationCount;
            public ulong ReadTransferCount;
            public ulong WriteTransferCount;
            public ulong OtherTransferCount;
        }

        [StructLayout(LayoutKind.Sequential)]
        private struct JOBOBJECT_EXTENDED_LIMIT_INFORMATION
        {
            public JOBOBJECT_BASIC_LIMIT_INFORMATION BasicLimitInformation;
            public IO_COUNTERS IoInfo;
            public UIntPtr ProcessMemoryLimit;
            public UIntPtr JobMemoryLimit;
            public UIntPtr PeakProcessMemoryUsed;
            public UIntPtr PeakJobMemoryUsed;
        }

        [DllImport("kernel32.dll", CharSet = CharSet.Unicode)]
        private static extern IntPtr CreateJobObject(IntPtr attributes, string name);

        [DllImport("kernel32.dll")]
        private static extern bool SetInformationJobObject(IntPtr job, int infoClass, IntPtr info, uint length);

        [DllImport("kernel32.dll")]
        private static extern bool AssignProcessToJobObject(IntPtr job, IntPtr process);
    }

    internal static class Log
    {
        private static readonly object Gate = new object();

        public static string DesktopLog
        {
            get { return Path.Combine(AppPaths.LogsDir, "desktop.log"); }
        }

        public static void Write(string message)
        {
            Append(DesktopLog, message);
        }

        public static void Append(string path, string message)
        {
            lock (Gate)
            {
                try
                {
                    Directory.CreateDirectory(Path.GetDirectoryName(path));

                    if (File.Exists(path) && new FileInfo(path).Length > 5 * 1024 * 1024)
                    {
                        File.Delete(path);
                    }

                    File.AppendAllText(path, "[" + DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss") + "] " + message + Environment.NewLine);
                }
                catch (Exception)
                {
                    // Logging must never take the app down.
                }
            }
        }
    }
}
