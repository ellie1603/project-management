using System;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Net;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace Bmpc.Desktop
{
    /// <summary>
    /// First-run setup and later "Desktop settings": choose between the offline
    /// local server and a hosted BMPC server, and back up or restore offline data.
    /// </summary>
    internal sealed class SettingsForm : Form
    {
        private readonly DesktopConfig config;
        private readonly LocalServer server;
        private readonly bool firstRun;
        private readonly int contentWidth = Ui.S(520);

        private RadioButton localOption;
        private RadioButton serverOption;
        private TextBox serverUrl;
        private Button testButton;
        private Label testResult;
        private Label dataStatus;
        private Panel body;
        private Button saveButton;

        public SettingsForm(DesktopConfig config, LocalServer server, bool firstRun)
        {
            this.config = config;
            this.server = server;
            this.firstRun = firstRun;

            Text = firstRun ? "Set up " + AppPaths.ProductName : "Desktop settings";
            Icon = Program.AppIcon;
            BackColor = Color.White;
            Font = Ui.Font(9.75f);
            FormBorderStyle = FormBorderStyle.FixedDialog;
            MaximizeBox = false;
            MinimizeBox = false;
            ShowInTaskbar = firstRun;
            StartPosition = firstRun ? FormStartPosition.CenterScreen : FormStartPosition.CenterParent;

            Build();
        }

        /// <summary>Set when a restore replaced the data, so the app must restart.</summary>
        public bool RestartNeeded { get; private set; }

        private void Build()
        {
            int margin = Ui.S(28);
            int y = margin;

            body = new Panel { Location = Point.Empty, BackColor = Color.White };

            // Header
            var logo = new PictureBox
            {
                Image = Ui.LoadLogo(),
                SizeMode = PictureBoxSizeMode.Zoom,
                Size = new Size(Ui.S(52), Ui.S(52)),
                Location = new Point(margin, y),
            };

            Label heading = Ui.Label(firstRun ? "Welcome to " + AppPaths.ProductName : "Desktop settings", Ui.Semibold(14f), Ui.Text, contentWidth - Ui.S(68));
            heading.Location = new Point(margin + Ui.S(68), y + Ui.S(2));

            Label intro = Ui.Label("Choose how this computer runs the system. You can change this later in Desktop settings (right-click the title bar).", Ui.Font(9.75f), Ui.SecondaryText, contentWidth - Ui.S(68));
            intro.Location = new Point(margin + Ui.S(68), heading.Bottom + Ui.S(2));

            body.Controls.AddRange(new Control[] { logo, heading, intro });
            y = Math.Max(logo.Bottom, intro.Bottom) + Ui.S(22);

            // Option 1: offline
            bool hasRuntime = AppPaths.HasLocalRuntime;
            localOption = new RadioButton { Text = "Run on this computer (offline)", Enabled = hasRuntime };
            OptionCard localCard = new OptionCard(localOption,
                hasRuntime
                    ? "Works without internet. Includes its own secure database, so all projects, documents and reports are stored on this PC. Best for a single office computer."
                    : "Not included in this package. Use the full installer to run offline.",
                contentWidth);
            localCard.Location = new Point(margin, y);
            body.Controls.Add(localCard);
            y = localCard.Bottom + Ui.S(12);

            // Option 2: hosted server
            serverOption = new RadioButton { Text = "Connect to the BMPC server" };
            OptionCard serverCard = new OptionCard(serverOption,
                "Everyone shares the same live data. Needs a network connection to the cooperative's BMPC server.",
                contentWidth);

            serverUrl = new TextBox { Font = Ui.Font(10f), Text = config.ServerUrl ?? "", BorderStyle = BorderStyle.FixedSingle };
            Ui.SetCueBanner(serverUrl, "https://pm.example.coop");
            testButton = Ui.SecondaryButton("Test");
            testResult = Ui.Label("", Ui.Font(9f), Ui.SecondaryText, serverCard.InnerWidth);

            serverCard.AddExtra(serverUrl, testButton, testResult);
            serverCard.Location = new Point(margin, y);
            body.Controls.Add(serverCard);
            y = serverCard.Bottom + Ui.S(12);

            // The radios sit in separate cards, so keep them mutually exclusive by hand.
            localOption.CheckedChanged += (s, e) => { if (localOption.Checked) { serverOption.Checked = false; } };
            serverOption.CheckedChanged += (s, e) => { if (serverOption.Checked) { localOption.Checked = false; } };
            serverUrl.TextChanged += (s, e) => { serverOption.Checked = true; testResult.Text = ""; };
            testButton.Click += async (s, e) => await TestConnectionAsync();

            if (config.Mode == DesktopConfig.ModeServer || !hasRuntime)
            {
                serverOption.Checked = true;
            }
            else
            {
                localOption.Checked = true;
            }

            // Offline data tools (settings only)
            if (!firstRun && config.IsLocal && hasRuntime)
            {
                y += Ui.S(8);
                Label dataHeading = Ui.Label("Offline data", Ui.Semibold(11f), Ui.Text, contentWidth);
                dataHeading.Location = new Point(margin, y);
                body.Controls.Add(dataHeading);
                y = dataHeading.Bottom + Ui.S(2);

                Label dataInfo = Ui.Label("Stored in " + AppPaths.DataDir + ". Back up regularly; a backup holds the database and every uploaded document.", Ui.Font(9f), Ui.SecondaryText, contentWidth);
                dataInfo.Location = new Point(margin, y);
                body.Controls.Add(dataInfo);
                y = dataInfo.Bottom + Ui.S(10);

                Button backup = Ui.SecondaryButton("Back up data...");
                Button restore = Ui.SecondaryButton("Restore backup...");
                Button folder = Ui.SecondaryButton("Open data folder");
                backup.Location = new Point(margin, y);
                restore.Location = new Point(backup.Right + Ui.S(8), y);
                folder.Location = new Point(restore.Right + Ui.S(8), y);
                backup.Enabled = restore.Enabled = server != null;
                backup.Click += async (s, e) => await BackupAsync();
                restore.Click += async (s, e) => await RestoreAsync();
                folder.Click += (s, e) => Ui.OpenFolder(AppPaths.DataDir);
                body.Controls.AddRange(new Control[] { backup, restore, folder });
                y = backup.Bottom + Ui.S(6);

                dataStatus = Ui.Label(server == null ? "Open the app first to back up or restore." : "", Ui.Font(9f), Ui.SecondaryText, contentWidth);
                dataStatus.Location = new Point(margin, y);
                body.Controls.Add(dataStatus);
                y = dataStatus.Bottom;
            }

            // Footer
            y += Ui.S(20);
            var divider = new Panel { BackColor = Ui.Border, Location = new Point(0, y), Size = new Size(contentWidth + margin * 2, 1) };
            body.Controls.Add(divider);
            y += Ui.S(16);

            saveButton = Ui.PrimaryButton(firstRun ? "Continue" : "Save");
            Button cancel = Ui.SecondaryButton(firstRun ? "Quit" : "Cancel");
            saveButton.Location = new Point(margin + contentWidth - saveButton.Width, y);
            cancel.Location = new Point(saveButton.Left - Ui.S(8) - cancel.Width, y);
            saveButton.Click += (s, e) => Save();
            cancel.Click += (s, e) => { DialogResult = DialogResult.Cancel; Close(); };
            body.Controls.AddRange(new Control[] { cancel, saveButton });
            y = saveButton.Bottom + Ui.S(20);

            body.Size = new Size(contentWidth + margin * 2, y);
            ClientSize = body.Size;
            Controls.Add(body);

            AcceptButton = saveButton;
            CancelButton = cancel;
        }

        private void Save()
        {
            if (serverOption.Checked)
            {
                Uri uri;

                if (!TryParseServerUrl(serverUrl.Text, out uri))
                {
                    ShowTestResult("Enter the full server address, for example https://pm.example.coop", Ui.Danger);
                    serverUrl.Focus();
                    return;
                }

                config.Mode = DesktopConfig.ModeServer;
                config.ServerUrl = uri.GetLeftPart(UriPartial.Path).TrimEnd('/');
            }
            else
            {
                config.Mode = DesktopConfig.ModeLocal;
            }

            try
            {
                config.Save();
            }
            catch (Exception ex)
            {
                MessageBox.Show(this, "The settings could not be saved.\n\n" + ex.Message, Text, MessageBoxButtons.OK, MessageBoxIcon.Error);
                return;
            }

            DialogResult = DialogResult.OK;
            Close();
        }

        private async Task TestConnectionAsync()
        {
            Uri uri;

            if (!TryParseServerUrl(serverUrl.Text, out uri))
            {
                ShowTestResult("Enter the full server address, for example https://pm.example.coop", Ui.Danger);
                return;
            }

            testButton.Enabled = false;
            ShowTestResult("Connecting...", Ui.SecondaryText);

            string result = await Task.Run(() => Probe(new Uri(uri.GetLeftPart(UriPartial.Path).TrimEnd('/') + "/up")));

            testButton.Enabled = true;
            ShowTestResult(result ?? "Connected. The BMPC server is reachable.", result == null ? Ui.Success : Ui.Danger);
        }

        /// <summary>Returns null when the server's health check answers, otherwise a reason.</summary>
        private static string Probe(Uri health)
        {
            try
            {
                var request = (HttpWebRequest)WebRequest.Create(health);
                request.Timeout = 10000;
                request.UserAgent = "BMPCDesktop/" + MainForm.Version;

                using (var response = (HttpWebResponse)request.GetResponse())
                {
                    return response.StatusCode == HttpStatusCode.OK ? null : "The server answered with HTTP " + (int)response.StatusCode + ".";
                }
            }
            catch (WebException ex)
            {
                var response = ex.Response as HttpWebResponse;

                return response != null
                    ? "The server answered with HTTP " + (int)response.StatusCode + ". Check the address."
                    : "Could not connect: " + ex.Message;
            }
            catch (Exception ex)
            {
                return "Could not connect: " + ex.Message;
            }
        }

        private async Task BackupAsync()
        {
            using (var dialog = new SaveFileDialog
            {
                Title = "Back up BMPC data",
                Filter = "BMPC backup (*.zip)|*.zip",
                FileName = "BMPC-backup-" + DateTime.Now.ToString("yyyy-MM-dd-HHmm") + ".zip",
                InitialDirectory = Environment.GetFolderPath(Environment.SpecialFolder.MyDocuments),
            })
            {
                if (dialog.ShowDialog(this) != DialogResult.OK)
                {
                    return;
                }

                await RunDataTaskAsync(status => BackupService.Backup(server, dialog.FileName, status), "Backup saved to " + dialog.FileName);
            }
        }

        private async Task RestoreAsync()
        {
            using (var dialog = new OpenFileDialog
            {
                Title = "Restore a BMPC backup",
                Filter = "BMPC backup (*.zip)|*.zip",
            })
            {
                if (dialog.ShowDialog(this) != DialogResult.OK)
                {
                    return;
                }

                DialogResult confirm = MessageBox.Show(this,
                    "Restoring replaces ALL current projects, accounts and documents on this computer with the contents of the backup.\n\n"
                    + "Your current documents folder is kept once as storage\\app.before-restore.\n\nContinue?",
                    "Restore backup", MessageBoxButtons.YesNo, MessageBoxIcon.Warning, MessageBoxDefaultButton.Button2);

                if (confirm != DialogResult.Yes)
                {
                    return;
                }

                bool ok = await RunDataTaskAsync(status => BackupService.Restore(server, dialog.FileName, status), "Restore complete.");

                if (ok)
                {
                    config.OptimizedFor = null;
                    config.Save();
                    RestartNeeded = true;
                    MessageBox.Show(this, "The backup was restored. The app will now restart.", Text, MessageBoxButtons.OK, MessageBoxIcon.Information);
                    DialogResult = DialogResult.OK;
                    Close();
                }
            }
        }

        private async Task<bool> RunDataTaskAsync(Action<Action<string>> work, string doneMessage)
        {
            body.Enabled = false;
            UseWaitCursor = true;

            try
            {
                Action<string> report = message => BeginInvoke((Action)(() => SetDataStatus(message, Ui.SecondaryText)));
                await Task.Run(() => work(report));
                SetDataStatus(doneMessage, Ui.Success);
                return true;
            }
            catch (Exception ex)
            {
                Log.Write("Data task failed: " + ex);
                SetDataStatus("Failed: " + ex.Message, Ui.Danger);
                return false;
            }
            finally
            {
                UseWaitCursor = false;
                body.Enabled = true;
            }
        }

        private void SetDataStatus(string message, Color color)
        {
            if (dataStatus == null)
            {
                return;
            }

            dataStatus.Text = message;
            dataStatus.ForeColor = color;
            GrowToFit(dataStatus);
        }

        private void ShowTestResult(string message, Color color)
        {
            testResult.Text = message;
            testResult.ForeColor = color;
        }

        /// <summary>Lets a status label wrap without pushing the footer off the form.</summary>
        private void GrowToFit(Label label)
        {
            int before = label.Height;
            Ui.FitHeight(label, label.Width);
            int delta = label.Height - before;

            if (delta <= 0)
            {
                return;
            }

            foreach (Control control in body.Controls)
            {
                if (control.Top > label.Top)
                {
                    control.Top += delta;
                }
            }

            body.Height += delta;
            ClientSize = body.Size;
        }

        private static bool TryParseServerUrl(string text, out Uri uri)
        {
            string value = (text ?? "").Trim();

            if (value.Length > 0 && !value.Contains("://"))
            {
                value = "https://" + value;
            }

            return Uri.TryCreate(value, UriKind.Absolute, out uri)
                && (uri.Scheme == Uri.UriSchemeHttp || uri.Scheme == Uri.UriSchemeHttps)
                && uri.Host.Length > 0;
        }

        /// <summary>A rounded, clickable card wrapping a radio button and its description.</summary>
        private sealed class OptionCard : Panel
        {
            private readonly RadioButton radio;
            private readonly int padding = Ui.S(16);
            private int bottom;

            public OptionCard(RadioButton radio, string description, int width)
            {
                this.radio = radio;
                DoubleBuffered = true;
                BackColor = Color.White;
                Width = width;

                radio.Font = Ui.Semibold(10.5f);
                radio.ForeColor = Ui.Text;
                radio.AutoSize = true;
                radio.Location = new Point(padding, padding);
                radio.BackColor = Color.White;
                radio.CheckedChanged += (s, e) => Invalidate();

                Label text = Ui.Label(description, Ui.Font(9.25f), Ui.SecondaryText, InnerWidth);
                text.Location = new Point(padding + Ui.S(20), radio.Bottom + Ui.S(4));
                text.Click += (s, e) => Select();

                Controls.Add(radio);
                Controls.Add(text);
                bottom = text.Bottom;
                Height = bottom + padding;

                Click += (s, e) => Select();
                Cursor = Cursors.Hand;
            }

            public int InnerWidth
            {
                get { return Width - padding * 2 - Ui.S(20); }
            }

            /// <summary>Adds a text box with a button beside it and a status line below.</summary>
            public void AddExtra(TextBox box, Button button, Label status)
            {
                int left = padding + Ui.S(20);
                int top = bottom + Ui.S(10);

                button.Height = Ui.S(30);
                button.Width = Ui.S(72);
                box.Width = InnerWidth - button.Width - Ui.S(8);
                box.Location = new Point(left, top + (button.Height - box.Height) / 2);
                button.Location = new Point(box.Right + Ui.S(8), top);
                status.Location = new Point(left, button.Bottom + Ui.S(6));
                status.Height = status.Font.Height * 2;

                Controls.Add(box);
                Controls.Add(button);
                Controls.Add(status);

                bottom = status.Bottom;
                Height = bottom + padding;
            }

            private new void Select()
            {
                if (radio.Enabled)
                {
                    radio.Checked = true;
                }
            }

            protected override void OnPaint(PaintEventArgs e)
            {
                base.OnPaint(e);
                e.Graphics.SmoothingMode = SmoothingMode.AntiAlias;

                var bounds = new Rectangle(0, 0, Width - 1, Height - 1);
                Color color = radio.Checked ? Ui.Accent : Ui.Border;

                using (GraphicsPath path = Ui.RoundedRectangle(bounds, Ui.S(12)))
                using (var pen = new Pen(color, radio.Checked ? Ui.S(2) : 1))
                {
                    e.Graphics.DrawPath(pen, path);
                }
            }
        }
    }
}
