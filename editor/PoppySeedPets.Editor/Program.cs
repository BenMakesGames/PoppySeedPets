using Avalonia;
using PoppySeedPets.Editor.Sections.GroupedStrings;

namespace PoppySeedPets.Editor;

internal static class Program
{
    private static string? _repoRoot;

    /// <param name="args">Optionally, the repo to edit; defaults to the one this was built from.</param>
    [STAThread]
    public static void Main(string[] args)
    {
        if (args.Length > 0) _repoRoot = Path.GetFullPath(args[0]);
        BuildAvaloniaApp().StartWithClassicDesktopLifetime(args);
    }

    // Also used by the Avalonia designer.
    public static AppBuilder BuildAvaloniaApp() => AppBuilder.Configure<App>().UsePlatformDetect().LogToTrace();

    public static string RepoRoot => _repoRoot ??= FindRepoRoot();

    public static IReadOnlyList<ISection> CreateSections(string repoRoot) =>
    [
        new GroupedStringsView("Lyrics", Path.Combine(repoRoot, "api", "src", "Model", "Music.php"), "Lyrics"),
    ];

    private static string FindRepoRoot()
    {
        foreach (var start in new[] { AppContext.BaseDirectory, Environment.CurrentDirectory })
            for (var dir = new DirectoryInfo(start); dir is not null; dir = dir.Parent)
                if (File.Exists(Path.Combine(dir.FullName, "editor", "PoppySeedPets.Editor.slnx")))
                    return dir.FullName;

        throw new InvalidOperationException("Couldn't find the repo root (editor/PoppySeedPets.Editor.slnx); pass it as an argument.");
    }
}
