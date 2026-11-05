using Avalonia.Headless.XUnit;
using PoppySeedPets.Editor.Php;

namespace PoppySeedPets.Editor.Tests;

/// <summary>The sections the app really ships, against the repo's real files. Read-only.</summary>
public sealed class RealSectionsTests
{
    public static TheoryData<string, string, string> Lists => new()
    {
        { "Model/FortuneCookie.php", "App\\Model\\FortuneCookie", "Fortunes" },
        { "Service/PetActivity/DreamingService.php", "App\\Service\\PetActivity\\DreamingService", "Locations" },
        { "Model/PetShelterPet.php", "App\\Model\\PetShelterPet", "PetNames" },
        { "Service/BeehiveService.php", "App\\Service\\BeehiveService", "QueenNames" },
        { "Controller/Item/Blueprint/HouseFairyController.php", "App\\Controller\\Item\\Blueprint\\HouseFairyController", "FairyNames" },
        { "Service/PetActivity/Group/BandService.php", "App\\Service\\PetActivity\\Group\\BandService", "AdjectiveList" },
        { "Service/PetActivity/Group/BandService.php", "App\\Service\\PetActivity\\Group\\BandService", "NounList" },
        { "Service/PetActivity/Group/BandService.php", "App\\Service\\PetActivity\\Group\\BandService", "PluralNounList" },
        { "Service/PetActivity/Group/BandService.php", "App\\Service\\PetActivity\\Group\\BandService", "NumberList" },
        { "Service/PetActivity/Group/BandService.php", "App\\Service\\PetActivity\\Group\\BandService", "GroupNamePatterns" },
    };

    private static string Source(string file) => File.ReadAllText(Path.Combine(Repo.Root, "api", "src", file));

    [Theory]
    [MemberData(nameof(Lists))]
    public void ListsReadTheSameAsTheyDoInPhpAndWriteBackByteForByte(string file, string className, string constName)
    {
        var (codec, source) = (new StringListCodec(constName), Source(file));
        var read = codec.Read(source);

        Assert.Equal(RealPhp.Const(className, constName), read);
        Assert.Equal(source, codec.Write(source, read));
        Assert.Null(codec.Problem(read));
    }

    [Fact]
    public void TraderNamesReadTheSameAsTheyDoInPhpAndWriteBackByteForByte()
    {
        var (codec, source) = (new GroupedStringsCodec("TraderNames"), Source("Service/TraderService.php"));
        var read = codec.Read(source);

        Assert.Equal(RealPhp.Const("App\\Service\\TraderService", "TraderNames"), read.SelectMany(g => g.Strings));
        Assert.Equal(source, codec.Write(source, read));
        Assert.Null(codec.Problem(read));
    }

    [AvaloniaFact]
    public void SectionsAreAlphabetical()
    {
        var titles = Program.CreateSections(Repo.Root).Select(s => s.Title).ToList();
        Assert.Equal(titles.Order(StringComparer.OrdinalIgnoreCase), titles);
        Assert.Equal("Band adjectives", titles[0]);
        Assert.Equal("Trader names", titles[^1]);
    }

    [AvaloniaFact]
    public void EverySectionLoads()
    {
        foreach (var section in Program.CreateSections(Repo.Root))
        {
            section.Document.Refresh();
            Assert.True(section.Document.LoadError is null, $"{section.Title}: {section.Document.LoadError}");
            Assert.False(section.Document.IsDirty);
        }
    }
}
