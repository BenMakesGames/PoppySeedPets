using Avalonia;
using PoppySeedPets.Editor.Sections.GroupedStrings;
using PoppySeedPets.Editor.Sections.StringList;

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

    public static IReadOnlyList<ISection> CreateSections(string repoRoot)
    {
        string Api(string relative) => Path.Combine(repoRoot, "api", "src", relative);

        var band = Api("Service/PetActivity/Group/BandService.php");

        ISection[] sections =
        [
            new GroupedStringsView("Lyrics", Api("Model/Music.php"), "Lyrics"),
            new StringListView("Fortunes", Api("Model/FortuneCookie.php"), "Fortunes"),
            new StringListView("Dream locations", Api("Service/PetActivity/DreamingService.php"), "Locations"),
            new GroupedStringsView("Trader names", Api("Service/TraderService.php"), "TraderNames"),
            new StringListView("Pet names", Api("Model/PetShelterPet.php"), "PetNames", alphabetical: true),
            new StringListView("Bee queen names", Api("Service/BeehiveService.php"), "QueenNames", alphabetical: true),
            new StringListView("Fairy names", Api("Controller/Item/Blueprint/HouseFairyController.php"), "FairyNames", alphabetical: true),
            new StringListView("Band adjectives", band, "AdjectiveList"),
            new StringListView("Band nouns", band, "NounList"),
            new StringListView("Band plural nouns", band, "PluralNounList"),
            new StringListView("Band numbers", band, "NumberList"),
            new StringListView("Band name patterns", band, "GroupNamePatterns"),
        ];

        return sections.OrderBy(s => s.Title, StringComparer.OrdinalIgnoreCase).ToList();
    }

    private static string FindRepoRoot()
    {
        foreach (var start in new[] { AppContext.BaseDirectory, Environment.CurrentDirectory })
            for (var dir = new DirectoryInfo(start); dir is not null; dir = dir.Parent)
                if (File.Exists(Path.Combine(dir.FullName, "editor", "PoppySeedPets.Editor.slnx")))
                    return dir.FullName;

        throw new InvalidOperationException("Couldn't find the repo root (editor/PoppySeedPets.Editor.slnx); pass it as an argument.");
    }
}
