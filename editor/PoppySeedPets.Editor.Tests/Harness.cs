using System.Diagnostics;
using System.Text.Json;
using Avalonia;
using Avalonia.Controls;
using Avalonia.Headless;
using Avalonia.Interactivity;
using Avalonia.Media.Imaging;
using Avalonia.Threading;
using PoppySeedPets.Editor.Tests;

[assembly: AvaloniaTestApplication(typeof(HeadlessApp))]

namespace PoppySeedPets.Editor.Tests;

public static class HeadlessApp
{
    // Real (Skia) rendering, so frames can be captured for eyeballing.
    public static AppBuilder BuildAvaloniaApp() => AppBuilder.Configure<App>()
        .UseSkia()
        .UseHeadless(new AvaloniaHeadlessPlatformOptions { UseHeadlessDrawing = false });
}

/// <summary>The repo's own files: tests read these, and never write them.</summary>
public static class Repo
{
    public static string Root { get; } = Find();

    public static string MusicPhp => Path.Combine(Root, "api", "src", "Model", "Music.php");

    private static string Find()
    {
        for (var dir = new DirectoryInfo(AppContext.BaseDirectory); dir is not null; dir = dir.Parent)
            if (File.Exists(Path.Combine(dir.FullName, "editor", "PoppySeedPets.Editor.slnx")))
                return dir.FullName;

        throw new InvalidOperationException("Couldn't find the repo root.");
    }
}

/// <summary>A throwaway PHP file, so tests can save freely.</summary>
public sealed class TempPhpFile : IDisposable
{
    private readonly string _directory = Directory.CreateTempSubdirectory("pspeditor-tests-").FullName;

    public TempPhpFile(string text)
    {
        Path = System.IO.Path.Combine(_directory, "Music.php");
        Text = text;
    }

    public string Path { get; }

    public string Text
    {
        get => File.ReadAllText(Path);
        set => File.WriteAllText(Path, value);
    }

    public void Dispose() => Directory.Delete(_directory, recursive: true);
}

/// <summary>The real PHP's opinion, so the codec is checked against PHP instead of against itself.</summary>
public static class RealPhp
{
    /// <summary>Evaluates a file's <c>App\Model\Music::Lyrics</c>.</summary>
    /// <remarks>Skips the test if PHP isn't installed.</remarks>
    public static IReadOnlyList<string> Lyrics(string path)
    {
        var script = $"require '{path.Replace('\\', '/')}'; echo json_encode(App\\Model\\Music::Lyrics);";
        var start = new ProcessStartInfo("php") { RedirectStandardOutput = true, RedirectStandardError = true, ArgumentList = { "-r", script } };

        Process process;
        try
        {
            process = Process.Start(start)!;
        }
        catch (System.ComponentModel.Win32Exception)
        {
            Assert.Skip("PHP isn't installed.");
            throw;
        }

        var output = process.StandardOutput.ReadToEnd();
        process.WaitForExit();
        Assert.True(process.ExitCode == 0, output + process.StandardError.ReadToEnd());
        return JsonSerializer.Deserialize<string[]>(output)!;
    }
}

public static class Ui
{
    /// <summary>Lets posted work (dialogs opening, async continuations) run.</summary>
    public static void Settle() => Dispatcher.UIThread.RunJobs();

    /// <summary>Presses a button in the dialog <paramref name="owner"/> has open.</summary>
    public static void Answer(Window owner, string button)
    {
        Settle();
        var dialog = Assert.Single(owner.OwnedWindows);
        var pressed = FindAll<Button>(dialog).Single(b => (string?)b.Content == button);
        pressed.RaiseEvent(new RoutedEventArgs(Button.ClickEvent));
        Settle();
    }

    public static string DialogMessage(Window owner)
    {
        Settle();
        return FindAll<TextBlock>(Assert.Single(owner.OwnedWindows)).First(t => t.Text is { Length: > 0 }).Text!;
    }

    public static IEnumerable<T> FindAll<T>(ContentControl root) where T : Control =>
        root.Content is Panel panel ? Walk(panel).OfType<T>() : [];

    private static IEnumerable<Control> Walk(Panel panel) =>
        panel.Children.SelectMany(c => c is Panel inner ? Walk(inner).Prepend(c) : [c]);

    /// <summary>Set EDITOR_FRAMES to a directory to save a PNG of the window: the quickest way to see the editor without launching it.</summary>
    public static void Capture(Window window, string name)
    {
        if (Environment.GetEnvironmentVariable("EDITOR_FRAMES") is not { Length: > 0 } directory) return;

        Settle();
        AvaloniaHeadlessPlatform.ForceRenderTimerTick();
        Directory.CreateDirectory(directory);
        window.CaptureRenderedFrame()?.Save(Path.Combine(directory, name + ".png"), PngBitmapEncoderOptions.Default);
    }
}
