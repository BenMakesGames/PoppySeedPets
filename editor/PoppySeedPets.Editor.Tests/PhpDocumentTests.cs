using PoppySeedPets.Editor.Documents;
using PoppySeedPets.Editor.Php;

namespace PoppySeedPets.Editor.Tests;

public sealed class PhpDocumentTests : IDisposable
{
    private const string Text = "<?php\nconst array Lyrics = [\n    // One\n    'a',\n];\nconst int Other = 1;\n";

    private readonly TempPhpFile _file = new(Text);
    private readonly PhpDocument<IReadOnlyList<StringGroup>> _document;

    public PhpDocumentTests()
    {
        _document = new(_file.Path, new GroupedStringsCodec("Lyrics"), ItemsComparer<StringGroup>.Instance);
        _document.Reload();
    }

    public void Dispose() => _file.Dispose();

    private static IReadOnlyList<StringGroup> Lines(params string[] strings) => [new StringGroup("One", [.. strings])];

    [Fact]
    public void ChangingBackToWhatWasSavedIsClean()
    {
        _document.Change(Lines("b"));
        Assert.True(_document.IsDirty);

        _document.Change(Lines("a"));
        Assert.False(_document.IsDirty);
    }

    [Fact]
    public void UndoingBackToWhatWasSavedIsClean()
    {
        _document.Change(Lines("b"));
        _document.Undo();
        Assert.False(_document.IsDirty);
        Assert.Equal(Lines("a"), _document.Value);
    }

    [Fact]
    public void UndoingPastASaveIsDirty()
    {
        _document.Change(Lines("b"));
        Assert.Null(_document.Save());
        Assert.False(_document.IsDirty);

        _document.Undo();
        Assert.True(_document.IsDirty);
    }

    [Fact]
    public void ChangesWithTheSameKeyAreOneUndoStep()
    {
        _document.Change(Lines("ab"), "typing");
        _document.Change(Lines("abc"), "typing");
        _document.Change(Lines("abcd"), "typing somewhere else");
        _document.Change(Lines("abcde"));
        _document.Change(Lines("abcdef"));

        string[] steps = ["abcde", "abcd", "abc", "a"];
        foreach (var step in steps)
        {
            _document.Undo();
            Assert.Equal(Lines(step), _document.Value);
        }
    }

    [Fact]
    public void SavingEndsAMergedStep()
    {
        _document.Change(Lines("ab"), "typing");
        Assert.Null(_document.Save());
        _document.Change(Lines("abc"), "typing");

        _document.Undo();
        Assert.Equal(Lines("ab"), _document.Value);
    }

    [Fact]
    public void SavingOnlyTouchesTheChange()
    {
        _document.Change(Lines("b"));
        Assert.Null(_document.Save());
        Assert.Equal(Text.Replace("'a'", "'b'"), _file.Text);
        Assert.False(_document.HasOutsideChanges);
    }

    [Fact]
    public void SavingLeavesNoTempFile()
    {
        _document.Change(Lines("b"));
        _document.Save();
        Assert.Equal([_file.Path], Directory.GetFiles(Path.GetDirectoryName(_file.Path)!));
    }

    [Fact]
    public void SavingNonAsciiWritesUtf8WithoutABom()
    {
        _document.Change(Lines("오빤"));
        _document.Save();
        var bytes = File.ReadAllBytes(_file.Path);
        Assert.Equal("<?php"u8.ToArray(), bytes[..5]);
        Assert.Contains("오빤", System.Text.Encoding.UTF8.GetString(bytes));
    }

    [Fact]
    public void AProblemStopsTheSaveAndKeepsTheChange()
    {
        _document.Change(Lines(""));
        Assert.Contains("blank", _document.Save());
        Assert.True(_document.IsDirty);
        Assert.Equal(Text, _file.Text);
    }

    [Fact]
    public void AFileThatCantBeWrittenToIsAProblemNotACrash()
    {
        _document.Change(Lines("b"));
        using var lockedOpen = new FileStream(_file.Path, FileMode.Open, FileAccess.ReadWrite, FileShare.None);

        Assert.NotNull(_document.Save());
        Assert.True(_document.IsDirty);
    }

    [Fact]
    public void RefreshingPicksUpOutsideChanges()
    {
        _file.Text = Text.Replace("'a'", "'edited elsewhere'");
        Assert.True(_document.HasOutsideChanges);

        _document.Refresh();

        Assert.Equal(Lines("edited elsewhere"), _document.Value);
        Assert.False(_document.IsDirty);
    }

    [Fact]
    public void RefreshingNeverLosesUnsavedChanges()
    {
        _document.Change(Lines("mine"));
        _file.Text = Text.Replace("'a'", "'theirs'");

        _document.Refresh();

        Assert.Equal(Lines("mine"), _document.Value);
        Assert.True(_document.HasOutsideChanges);
    }

    [Fact]
    public void SavingKeepsOutsideChangesToTheRestOfTheFile()
    {
        _document.Change(Lines("mine"));
        _file.Text = Text.Replace("Other = 1", "Other = 2");

        Assert.Null(_document.Save());

        Assert.Equal(Text.Replace("Other = 1", "Other = 2").Replace("'a'", "'mine'"), _file.Text);
    }

    [Fact]
    public void AFileItCantUnderstandIsReportedThenRetried()
    {
        _file.Text = "<?php const array Lyrics = [ 1 ];";
        _document.Refresh();
        Assert.Null(_document.Value);
        Assert.Contains("only // comments", _document.LoadError);

        _file.Text = Text;
        _document.Refresh();
        Assert.Null(_document.LoadError);
        Assert.Equal(Lines("a"), _document.Value);
    }

    [Fact]
    public void AMissingFileIsReported()
    {
        var document = new PhpDocument<IReadOnlyList<StringGroup>>(_file.Path + ".nope", new GroupedStringsCodec("Lyrics"), ItemsComparer<StringGroup>.Instance);
        document.Refresh();
        Assert.NotNull(document.LoadError);
        Assert.False(document.IsDirty);
        Assert.Null(document.Save());
    }

    [Fact]
    public void AFileThatIsntUtf8IsRefused()
    {
        File.WriteAllBytes(_file.Path, [.. "<?php const array Lyrics = [ // H\n'"u8, 0xFF, .. "' ];"u8]);
        _document.Reload();
        Assert.NotNull(_document.LoadError);
    }
}
