using PoppySeedPets.Editor.Php;

namespace PoppySeedPets.Editor.Tests;

public sealed class GroupedStringsCodecTests
{
    private readonly GroupedStringsCodec _codec = new("Lyrics");

    // Has the oddities a re-render would "fix": whitespace-only lines, and no trailing comma.
    private const string Sample =
        "<?php\n" +
        "class Music\n" +
        "{\n" +
        "    const array Lyrics = [\n" +
        "        // One\n" +
        "        'a',\n" +
        "        'b',\n" +
        "\n" +
        "        // Two\n" +
        "        'it\\'s',\n" +
        "        \n" +
        "        // Three\n" +
        "        'c'\n" +
        "    ];\n" +
        "}\n";

    private static StringGroup Group(string heading, params string[] strings) => new(heading, [.. strings]);

    /// <summary>The lines removed from, and added to, Sample by an edit, as git would count them.</summary>
    private static (string[] Removed, string[] Added) Churn(string before, string after)
    {
        var (from, to) = (before.Split('\n').ToList(), after.Split('\n').ToList());
        while (from.Count > 0 && to.Count > 0 && from[0] == to[0]) (from, to) = (from[1..], to[1..]);
        while (from.Count > 0 && to.Count > 0 && from[^1] == to[^1]) (from, to) = (from[..^1], to[..^1]);
        return (from.ToArray(), to.ToArray());
    }

    private (string[] Removed, string[] Added) ChurnOf(Func<List<StringGroup>, IEnumerable<StringGroup>> edit)
    {
        var after = _codec.Write(Sample, edit(_codec.Read(Sample).ToList()).ToList());
        return Churn(Sample, after);
    }

    [Fact]
    public void ReadsGroups()
    {
        Assert.Equal([Group("One", "a", "b"), Group("Two", "it's"), Group("Three", "c")], _codec.Read(Sample));
    }

    [Fact]
    public void WritingBackWhatWasReadChangesNothing()
    {
        Assert.Equal(Sample, _codec.Write(Sample, _codec.Read(Sample)));
    }

    [Fact]
    public void TheRealFileRoundTripsByteForByte()
    {
        var text = File.ReadAllText(Repo.MusicPhp);
        Assert.Equal(text, _codec.Write(text, _codec.Read(text)));
    }

    [Fact]
    public void TheRealFileReadsTheSameAsItDoesInPhp()
    {
        var read = _codec.Read(File.ReadAllText(Repo.MusicPhp));
        Assert.Equal(RealPhp.Lyrics(Repo.MusicPhp), read.SelectMany(g => g.Strings));
    }

    [Fact]
    public void EditedFilesReadTheSameAsTheyDoInPhp()
    {
        using var file = new TempPhpFile(File.ReadAllText(Repo.MusicPhp));
        const string awkward = """it's \ a \' "test" \\ with // and /* and $vars \""";
        var groups = _codec.Read(file.Text).ToList();
        groups.RemoveAt(4);
        groups.Insert(0, Group("Awkward", awkward, "한국어"));
        groups.Add(Group("New at the end, with a 'quote' in", "x"));

        file.Text = _codec.Write(file.Text, groups);

        Assert.Equal(RealPhp.Lyrics(file.Path), groups.SelectMany(g => g.Strings));
    }

    [Fact]
    public void EditingTheRealFileChangesOnlyTheEditedLines()
    {
        var text = File.ReadAllText(Repo.MusicPhp);
        var groups = _codec.Read(text).ToList();

        // Near the end, where the file's stray whitespace is, and on the comma-less last line.
        var last = groups[^1];
        groups[^2] = groups[^2] with { Strings = groups[^2].Strings.SetItem(0, "edited") };
        groups[^1] = last with { Strings = last.Strings.SetItem(0, "also edited") };

        var (removed, added) = Churn(text, _codec.Write(text, groups));

        Assert.Equal(4, removed.Length); // the two lines, and the comment and blank line between them
        Assert.Equal("        'edited',", added[0]);
        Assert.Equal("        'also edited'", added[^1]);
        Assert.Equal(removed[1..^1], added[1..^1]);
    }

    [Fact]
    public void EditingAStringChangesOneLine()
    {
        var (removed, added) = ChurnOf(g => g.Select(x => x.Heading == "One" ? x with { Strings = x.Strings.SetItem(1, "B!") } : x));
        Assert.Equal(["        'b',"], removed);
        Assert.Equal(["        'B!',"], added);
    }

    [Fact]
    public void EditingTheLastStringKeepsItCommaless()
    {
        var (removed, added) = ChurnOf(g => g.Select(x => x.Heading == "Three" ? Group("Three", "C!") : x));
        Assert.Equal(["        'c'"], removed);
        Assert.Equal(["        'C!'"], added);
    }

    [Fact]
    public void RenamingAHeadingChangesOneLine()
    {
        var (removed, added) = ChurnOf(g => g.Select(x => x.Heading == "Two" ? x with { Heading = "2" } : x));
        Assert.Equal(["        // Two"], removed);
        Assert.Equal(["        // 2"], added);
    }

    [Fact]
    public void AddingAStringAddsOneLine()
    {
        var (removed, added) = ChurnOf(g => g.Select(x => x.Heading == "Two" ? x with { Strings = x.Strings.Add("new") } : x));
        Assert.Empty(removed);
        Assert.Equal(["        'new',"], added);
    }

    [Fact]
    public void AddingAStringToTheEndOnlyTouchesTheLineThatNeedsAComma()
    {
        var (removed, added) = ChurnOf(g => g.Select(x => x.Heading == "Three" ? x with { Strings = x.Strings.Add("new") } : x));
        Assert.Equal(["        'c'"], removed);
        Assert.Equal(["        'c',", "        'new',"], added);
    }

    [Fact]
    public void AddingAGroupToTheEnd()
    {
        var (removed, added) = ChurnOf(g => [.. g, Group("Four", "d")]);
        Assert.Equal(["        'c'"], removed);
        Assert.Equal(["        'c',", "", "        // Four", "        'd',"], added);
    }

    [Fact]
    public void AddingAGroupToTheStartLeavesABlankLineBetween()
    {
        var (removed, added) = ChurnOf(g => [Group("Zero", "z"), .. g]);
        Assert.Empty(removed);
        Assert.Equal(["        // Zero", "        'z',", ""], added);
    }

    [Fact]
    public void RemovingAMiddleGroupKeepsOddWhitespaceElsewhere()
    {
        var after = _codec.Write(Sample, [Group("One", "a", "b"), Group("Three", "c")]);
        Assert.Equal(Sample.Replace("\n        // Two\n        'it\\'s',\n", ""), after);
    }

    [Fact]
    public void RemovingTheFirstGroupLeavesNoBlankLineAfterTheBracket()
    {
        var after = _codec.Write(Sample, [Group("Two", "it's"), Group("Three", "c")]);
        Assert.Contains("= [\n        // Two\n", after);
    }

    [Fact]
    public void RemovingTheLastStringLeavesATrailingCommaAlone()
    {
        var (removed, added) = ChurnOf(g => g.Where(x => x.Heading != "Three"));
        Assert.Equal(["        ", "        // Three", "        'c'"], removed);
        Assert.Empty(added);
    }

    [Fact]
    public void MovingAStringBetweenGroupsOnlyTouchesThoseLines()
    {
        var after = _codec.Write(Sample, [Group("One", "a"), Group("Two", "it's", "b"), Group("Three", "c")]);
        Assert.Equal(Sample.Replace("        'b',\n", "").Replace("'it\\'s',\n", "'it\\'s',\n        'b',\n"), after);
    }

    [Fact]
    public void AGroupWithNoStringsRoundTrips()
    {
        var groups = new[] { Group("Empty"), Group("One", "a") };
        Assert.Equal(groups, _codec.Read(_codec.Write(Sample, groups)));
    }

    [Fact]
    public void EmptyingAndRefillingTheArrayWorks()
    {
        var empty = _codec.Write(Sample, []);
        Assert.Empty(_codec.Read(empty));

        var refilled = _codec.Write(empty, [Group("One", "a")]);
        Assert.Contains("= [\n        // One\n        'a',\n    ];", refilled);
    }

    [Fact]
    public void WindowsLineEndingsAreKept()
    {
        var crlf = Sample.Replace("\n", "\r\n");
        var after = _codec.Write(crlf, [.. _codec.Read(crlf), Group("Four", "d")]);
        Assert.DoesNotContain("\n", after.Replace("\r\n", ""));
    }

    [Theory]
    [InlineData("'string before a heading'", "needs a // heading")]
    [InlineData("// H\n'a' 'b'", "expected a comma")]
    [InlineData("// H\n\"double-quoted\"", "only // headings")]
    [InlineData("// H\n'a' . 'b'", "expected a comma")]
    [InlineData("// H\n/* block */", "only // headings")]
    [InlineData("# H", "only // headings")]
    [InlineData("// H\n'key' => 'value'", "expected a comma")]
    [InlineData("// H\n'a', // trailing note", "line of its own")]
    public void AnythingElseInTheArrayIsRefusedNotDropped(string body, string message)
    {
        var exception = Assert.Throws<PhpSourceException>(() => _codec.Read($"<?php\nconst array Lyrics = [\n{body}\n];"));
        Assert.Contains(message, exception.Message);
    }

    [Fact]
    public void ReadRefusalsSayWhichLine()
    {
        var exception = Assert.Throws<PhpSourceException>(() => _codec.Read("<?php\nconst array Lyrics = [\n// H\n1,\n];"));
        Assert.Contains("Line 4 (Lyrics)", exception.Message);
    }

    [Theory]
    [InlineData("", "ok", "no heading")]
    [InlineData("   ", "ok", "no heading")]
    [InlineData("a\nb", "ok", "line break")]
    [InlineData("a ?> b", "ok", "?>")]
    [InlineData("H", "", "blank")]
    [InlineData("H", "a\nb", "line break")]
    public void ProblemsAreFoundBeforeSaving(string heading, string value, string message)
    {
        Assert.Contains(message, _codec.Problem([Group(heading, value)]));
    }

    [Fact]
    public void ValidGroupsHaveNoProblems()
    {
        Assert.Null(_codec.Problem(_codec.Read(File.ReadAllText(Repo.MusicPhp))));
    }
}
