using System;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.IO;
using System.Runtime.InteropServices;
using System.Windows.Forms;

namespace Bmpc.Desktop
{
    /// <summary>Shared look for the launcher's native windows: iOS-like colours, Segoe UI, DPI scaling.</summary>
    internal static class Ui
    {
        public static readonly Color Accent = Color.FromArgb(0, 122, 255);
        public static readonly Color AccentPressed = Color.FromArgb(0, 98, 204);
        public static readonly Color Text = Color.FromArgb(28, 28, 30);
        public static readonly Color SecondaryText = Color.FromArgb(108, 108, 112);
        public static readonly Color Border = Color.FromArgb(222, 222, 228);
        public static readonly Color Surface = Color.FromArgb(242, 242, 247);
        public static readonly Color SurfacePressed = Color.FromArgb(225, 225, 232);
        public static readonly Color Danger = Color.FromArgb(200, 30, 40);
        public static readonly Color Success = Color.FromArgb(36, 138, 61);

        private static readonly float ScaleFactor = ComputeScale();

        /// <summary>Scales a 96-DPI pixel value to the current display.</summary>
        public static int S(int pixels)
        {
            return (int)Math.Round(pixels * ScaleFactor);
        }

        public static Font Font(float points, FontStyle style = FontStyle.Regular)
        {
            return new Font("Segoe UI", points, style);
        }

        public static Font Semibold(float points)
        {
            return new Font("Segoe UI Semibold", points);
        }

        public static Label Label(string text, Font font, Color color, int width)
        {
            var label = new Label
            {
                Text = text,
                Font = font,
                ForeColor = color,
                AutoSize = false,
                BackColor = Color.Transparent,
                UseMnemonic = false,
            };

            FitHeight(label, width);
            return label;
        }

        /// <summary>Sizes a wrapping label to its text at the given width.</summary>
        public static void FitHeight(Label label, int width)
        {
            Size preferred = label.GetPreferredSize(new Size(width, 0));
            label.Size = new Size(width, Math.Max(preferred.Height, label.Font.Height));
        }

        public static Button PrimaryButton(string text)
        {
            return StyledButton(text, Accent, AccentPressed, Color.White);
        }

        public static Button SecondaryButton(string text)
        {
            return StyledButton(text, Surface, SurfacePressed, Text);
        }

        private static Button StyledButton(string text, Color back, Color pressed, Color fore)
        {
            var button = new Button
            {
                Text = text,
                Font = Semibold(9.75f),
                ForeColor = fore,
                BackColor = back,
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand,
                UseMnemonic = false,
                UseVisualStyleBackColor = false,
            };

            button.FlatAppearance.BorderSize = 0;
            button.FlatAppearance.MouseOverBackColor = pressed;
            button.FlatAppearance.MouseDownBackColor = pressed;

            Size preferred = TextRenderer.MeasureText(text, button.Font);
            button.Size = new Size(Math.Max(S(96), preferred.Width + S(32)), S(36));

            return button;
        }

        public static void SetCueBanner(TextBox box, string text)
        {
            box.HandleCreated += (s, e) => SendMessage(box.Handle, EM_SETCUEBANNER, (IntPtr)1, text);
        }

        public static Image LoadLogo()
        {
            try
            {
                if (File.Exists(AppPaths.LogoFile))
                {
                    using (var stream = new MemoryStream(File.ReadAllBytes(AppPaths.LogoFile)))
                    {
                        return new Bitmap(stream);
                    }
                }
            }
            catch (Exception ex)
            {
                Log.Write("Logo could not be loaded: " + ex.Message);
            }

            return Program.AppIcon != null ? new Icon(Program.AppIcon, 256, 256).ToBitmap() : null;
        }

        public static GraphicsPath RoundedRectangle(Rectangle bounds, int radius)
        {
            int diameter = radius * 2;
            var path = new GraphicsPath();

            path.AddArc(bounds.X, bounds.Y, diameter, diameter, 180, 90);
            path.AddArc(bounds.Right - diameter, bounds.Y, diameter, diameter, 270, 90);
            path.AddArc(bounds.Right - diameter, bounds.Bottom - diameter, diameter, diameter, 0, 90);
            path.AddArc(bounds.X, bounds.Bottom - diameter, diameter, diameter, 90, 90);
            path.CloseFigure();

            return path;
        }

        public static void OpenFolder(string path)
        {
            try
            {
                System.IO.Directory.CreateDirectory(path);
                System.Diagnostics.Process.Start("explorer.exe", "\"" + path + "\"");
            }
            catch (Exception ex)
            {
                MessageBox.Show("Could not open " + path + "\n\n" + ex.Message, AppPaths.ProductName, MessageBoxButtons.OK, MessageBoxIcon.Warning);
            }
        }

        private static float ComputeScale()
        {
            using (Graphics graphics = Graphics.FromHwnd(IntPtr.Zero))
            {
                return graphics.DpiX / 96f;
            }
        }

        private const int EM_SETCUEBANNER = 0x1501;

        [DllImport("user32.dll", CharSet = CharSet.Unicode)]
        private static extern IntPtr SendMessage(IntPtr hWnd, int msg, IntPtr wParam, string lParam);
    }

    /// <summary>Thin indeterminate progress bar in the iOS style.</summary>
    internal sealed class ActivityBar : Control
    {
        private readonly Timer timer = new Timer { Interval = 16 };
        private float position;

        public ActivityBar()
        {
            SetStyle(ControlStyles.AllPaintingInWmPaint | ControlStyles.OptimizedDoubleBuffer | ControlStyles.UserPaint | ControlStyles.ResizeRedraw, true);
            timer.Tick += (s, e) =>
            {
                position = (position + 0.012f) % 1.4f;
                Invalidate();
            };
        }

        public bool Running
        {
            get { return timer.Enabled; }
            set { timer.Enabled = value; Invalidate(); }
        }

        protected override void OnPaint(PaintEventArgs e)
        {
            e.Graphics.SmoothingMode = SmoothingMode.AntiAlias;
            e.Graphics.Clear(Parent != null ? Parent.BackColor : BackColor);

            int radius = Math.Max(1, Height / 2);
            var track = new Rectangle(0, 0, Width - 1, Height - 1);

            using (GraphicsPath path = Ui.RoundedRectangle(track, radius))
            using (var brush = new SolidBrush(Ui.Border))
            {
                e.Graphics.FillPath(brush, path);
            }

            if (!Running)
            {
                return;
            }

            int segment = Width / 3;
            int x = (int)((position - 0.4f) * Width);
            Rectangle bar = Rectangle.Intersect(new Rectangle(x, 0, segment, Height - 1), track);

            if (bar.Width > Height)
            {
                using (GraphicsPath path = Ui.RoundedRectangle(bar, radius))
                using (var brush = new SolidBrush(Ui.Accent))
                {
                    e.Graphics.FillPath(brush, path);
                }
            }
        }

        protected override void Dispose(bool disposing)
        {
            if (disposing)
            {
                timer.Dispose();
            }

            base.Dispose(disposing);
        }
    }
}
