using System;
using System.Drawing;
using System.Windows.Forms;

namespace Bmpc.Desktop
{
    /// <summary>Startup screen shown over the web view: progress while starting, details and actions on failure.</summary>
    internal sealed class SplashPanel : Panel
    {
        private readonly Panel content = new Panel { BackColor = Color.White };
        private readonly PictureBox logo;
        private readonly Label title;
        private readonly Label subtitle;
        private readonly Label status;
        private readonly ActivityBar activity;
        private readonly TextBox details;
        private readonly FlowLayoutPanel actions;
        private readonly int width = Ui.S(520);

        public event EventHandler RetryClicked;
        public event EventHandler SettingsClicked;

        public SplashPanel()
        {
            BackColor = Color.White;
            DoubleBuffered = true;

            logo = new PictureBox
            {
                Image = Ui.LoadLogo(),
                SizeMode = PictureBoxSizeMode.Zoom,
                Size = new Size(Ui.S(112), Ui.S(112)),
            };

            title = Ui.Label(AppPaths.ProductName, Ui.Semibold(17f), Ui.Text, width);
            title.TextAlign = ContentAlignment.MiddleCenter;

            subtitle = Ui.Label("Barbaza Multi-Purpose Cooperative", Ui.Font(10f), Ui.SecondaryText, width);
            subtitle.TextAlign = ContentAlignment.MiddleCenter;

            status = Ui.Label("Starting...", Ui.Font(10f), Ui.SecondaryText, width);
            status.TextAlign = ContentAlignment.MiddleCenter;

            activity = new ActivityBar { Size = new Size(Ui.S(200), Ui.S(4)), Running = true };

            details = new TextBox
            {
                Multiline = true,
                ReadOnly = true,
                ScrollBars = ScrollBars.Vertical,
                BorderStyle = BorderStyle.None,
                BackColor = Ui.Surface,
                ForeColor = Ui.Text,
                Font = new Font("Consolas", 9f),
                Size = new Size(width, Ui.S(150)),
                Visible = false,
            };

            actions = new FlowLayoutPanel
            {
                FlowDirection = FlowDirection.LeftToRight,
                WrapContents = false,
                AutoSize = true,
                AutoSizeMode = AutoSizeMode.GrowAndShrink,
                BackColor = Color.White,
                Visible = false,
            };

            Button retry = Ui.PrimaryButton("Try again");
            retry.Click += (s, e) => Raise(RetryClicked);
            Button settings = Ui.SecondaryButton("Desktop settings");
            settings.Click += (s, e) => Raise(SettingsClicked);
            Button logs = Ui.SecondaryButton("Open logs");
            logs.Click += (s, e) => Ui.OpenFolder(AppPaths.LogsDir);

            foreach (Button button in new[] { retry, settings, logs })
            {
                button.Margin = new Padding(Ui.S(4), 0, Ui.S(4), 0);
                actions.Controls.Add(button);
            }

            content.Controls.AddRange(new Control[] { logo, title, subtitle, status, activity, details, actions });
            Controls.Add(content);

            Arrange();
        }

        public void ShowProgress(string message)
        {
            status.Text = message;
            status.ForeColor = Ui.SecondaryText;
            activity.Visible = true;
            activity.Running = true;
            details.Visible = false;
            actions.Visible = false;
            Visible = true;
            BringToFront();
            Arrange();
        }

        public void ShowError(string headline, string detail)
        {
            status.Text = headline;
            status.ForeColor = Ui.Danger;
            activity.Running = false;
            activity.Visible = false;
            details.Text = (detail ?? "").Replace("\r\n", "\n").Replace("\n", "\r\n");
            details.Visible = details.Text.Length > 0;
            actions.Visible = true;
            Visible = true;
            BringToFront();
            Arrange();
        }

        protected override void OnResize(EventArgs eventargs)
        {
            base.OnResize(eventargs);
            Arrange();
        }

        private void Arrange()
        {
            if (content == null || title == null)
            {
                return;
            }

            Ui.FitHeight(status, width);

            int y = 0;
            logo.Location = new Point((width - logo.Width) / 2, y);
            y = logo.Bottom + Ui.S(20);
            title.Location = new Point(0, y);
            y = title.Bottom + Ui.S(2);
            subtitle.Location = new Point(0, y);
            y = subtitle.Bottom + Ui.S(28);
            status.Location = new Point(0, y);
            y = status.Bottom + Ui.S(14);

            if (activity.Visible)
            {
                activity.Location = new Point((width - activity.Width) / 2, y);
                y = activity.Bottom;
            }

            if (details.Visible)
            {
                details.Location = new Point(0, y);
                y = details.Bottom + Ui.S(18);
            }

            if (actions.Visible)
            {
                Size size = actions.GetPreferredSize(Size.Empty);
                actions.Size = size;
                actions.Location = new Point((width - size.Width) / 2, y);
                y = actions.Bottom;
            }

            content.Size = new Size(width, y + Ui.S(4));
            content.Location = new Point(Math.Max(0, (ClientSize.Width - content.Width) / 2), Math.Max(Ui.S(16), (ClientSize.Height - content.Height) / 2 - Ui.S(24)));
        }

        private void Raise(EventHandler handler)
        {
            if (handler != null)
            {
                handler(this, EventArgs.Empty);
            }
        }
    }
}
