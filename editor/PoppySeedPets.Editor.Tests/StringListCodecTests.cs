using PoppySeedPets.Editor.Php;

namespace PoppySeedPets.Editor.Tests;

public sealed class StringListCodecTests
{
    private readonly StringListCodec _codec = new("Names");

    // Packed, with runs separated by a blank line, and no trailing comma, as the real name lists are.
    private const string Packed =
        "<?php\n" +
        "class Names\n" +
        "{\n" +
        "    const array Names = [\n" +
        "        'Aaron', 'Abby', 'Adam',\n" +
        "        'Alma', 'Amos',\n" +
        "\n" +
        "        'Bea', 'Ben',\n" +
        "        'Bo', 'Bram'\n" +
        "    ];\n" +
        "}\n";

    private string Edit(string source, Func<List<string>, IEnumerable<string>> edit) =>
        _codec.Write(source, edit(_codec.Read(source).ToList()).ToList());

    [Fact]
    public void ReadsStringsFromPackedLines()
    {
        Assert.Equal(["Aaron", "Abby", "Adam", "Alma", "Amos", "Bea", "Ben", "Bo", "Bram"], _codec.Read(Packed));
    }

    [Fact]
    public void WritingBackWhatWasReadChangesNothing()
    {
        Assert.Equal(Packed, _codec.Write(Packed, _codec.Read(Packed)));
    }

    [Fact]
    public void EditingANameInPlaceStaysOnItsLine()
    {
        var after = Edit(Packed, n => n.Select(x => x == "Abby" ? "Abigail" : x));
        Assert.Equal(Packed.Replace("'Abby'", "'Abigail'"), after);
    }

    [Fact]
    public void AddingANameJoinsItsLine()
    {
        var after = Edit(Packed, n => { n.Insert(1, "Abe"); return n; });
        Assert.Equal(Packed.Replace("'Aaron', 'Abby'", "'Aaron', 'Abe', 'Abby'"), after);
    }

    [Fact]
    public void AddingANameAtTheEndOfALineStaysOnIt()
    {
        var after = Edit(Packed, n => { n.Insert(5, "Anna"); return n; });
        Assert.Equal(Packed.Replace("'Amos',", "'Amos', 'Anna',"), after);
    }

    [Fact]
    public void AddingANameThatWontFitWrapsToItsOwnLine()
    {
        var name = new string('x', 100);
        var after = Edit(Packed, n => { n.Insert(2, name); return n; });
        Assert.Equal(Packed.Replace("'Abby', 'Adam'", $"'Abby',\n        '{name}', 'Adam'"), after);
    }

    [Fact]
    public void AddingANameAtTheEndKeepsItOnTheLastLine()
    {
        var after = Edit(Packed, n => [.. n, "Cy"]);
        Assert.Equal(Packed.Replace("'Bram'\n", "'Bram', 'Cy',\n"), after);
    }

    [Fact]
    public void RemovingANameFromMidLineTakesOnlyIt()
    {
        var after = Edit(Packed, n => n.Where(x => x != "Abby"));
        Assert.Equal(Packed.Replace(" 'Abby',", ""), after);
    }

    [Fact]
    public void RemovingTheFirstNameOnALineKeepsTheNextOneThere()
    {
        var after = Edit(Packed, n => n.Where(x => x != "Alma"));
        Assert.Equal(Packed.Replace("'Alma', ", ""), after);
    }

    [Fact]
    public void RemovingTheFirstNameInARunKeepsTheBlankLineBeforeIt()
    {
        var after = Edit(Packed, n => n.Where(x => x != "Bea"));
        Assert.Equal(Packed.Replace("'Bea', ", ""), after);
    }

    [Fact]
    public void RemovingWholeLinesLeavesTheRestAlone()
    {
        var after = Edit(Packed, n => n.Where(x => x is not ("Alma" or "Amos")));
        Assert.Equal(Packed.Replace("        'Alma', 'Amos',\n", ""), after);
    }

    [Fact]
    public void RemovingTheVeryFirstNameKeepsTheNextOneOnTheFirstLine()
    {
        var after = Edit(Packed, n => n.Skip(1));
        Assert.Equal(Packed.Replace("'Aaron', ", ""), after);
    }

    [Fact]
    public void OneStringPerLineListsStayThatWay()
    {
        const string source = "<?php\nconst array Names = [\n    'a',\n    'b',\n];\n";
        Assert.Equal(source.Replace("'a',\n", "'a',\n    'new',\n"), Edit(source, n => { n.Insert(1, "new"); return n; }));
    }

    [Fact]
    public void DuplicatesAreFine()
    {
        const string source = "<?php\nconst array Names = [\n    'a', 'a',\n    'a',\n];\n";
        Assert.Equal(["a", "a"], _codec.Read(Edit(source, n => n.Skip(1))));
    }

    [Theory]
    [InlineData("'a', /* comment */ 'b'")]
    [InlineData("\"double\"")]
    [InlineData("self::Other")]
    public void AnythingElseInTheArrayIsRefusedNotDropped(string body)
    {
        var exception = Assert.Throws<PhpSourceException>(() => _codec.Read($"<?php\nconst array Names = [\n{body}\n];"));
        Assert.Contains("only // comments and single-quoted strings", exception.Message);
    }

    // As TraderNames is: notes on what the names below them mean.
    private const string Commented =
        "<?php\n" +
        "const array Names = [\n" +
        "    // Saffron\n" +
        "    'Azafran', 'Zaeafran',\n" +
        "\n" +
        "    // Platinum\n" +
        "    'Platin', 'Albalatin',\n" +
        "\n" +
        "    // Jade\n" +
        "    'Jade',\n" +
        "];\n";

    [Fact]
    public void CommentsArentEntriesAndSurviveARoundTrip()
    {
        Assert.Equal(["Azafran", "Zaeafran", "Platin", "Albalatin", "Jade"], _codec.Read(Commented));
        Assert.Equal(Commented, _codec.Write(Commented, _codec.Read(Commented)));
    }

    [Fact]
    public void EditingTheStringBelowACommentKeepsIt()
    {
        Assert.Equal(Commented.Replace("'Platin'", "'Platine'"), Edit(Commented, n => n.Select(x => x == "Platin" ? "Platine" : x)));
    }

    [Fact]
    public void RemovingTheFirstOfSeveralStringsBelowACommentKeepsIt()
    {
        Assert.Equal(Commented.Replace("'Platin', ", ""), Edit(Commented, n => n.Where(x => x != "Platin")));
    }

    [Fact]
    public void RemovingTheOnlyStringsBelowACommentRemovesIt()
    {
        Assert.Equal(Commented.Replace("\n    // Jade\n    'Jade',\n", ""), Edit(Commented, n => n.Where(x => x != "Jade")));
    }

    [Fact]
    public void RemovingTheVeryFirstStringsLeavesTheNextCommentAtTheTop()
    {
        var after = Edit(Commented, n => n.Skip(2));
        Assert.Equal(Commented.Replace("    // Saffron\n    'Azafran', 'Zaeafran',\n\n", ""), after);
    }

    [Fact]
    public void AddingToTheTopLeavesTheFirstCommentAboveItsString()
    {
        var after = Edit(Commented, n => { n.Insert(0, "New"); return n; });
        Assert.Equal(Commented.Replace("[\n    // Saffron", "[\n    'New',\n    // Saffron"), after);
    }

    [Fact]
    public void ACommentAfterTheLastStringIsLeftAlone()
    {
        const string source = "<?php\nconst array Names = [\n    'a',\n    'b',\n    // more to come\n];\n";
        Assert.Equal(source.Replace("'b',\n", "'b2',\n"), Edit(source, n => n.Select(x => x == "b" ? "b2" : x)));
    }

    [Fact]
    public void ACommentAfterAStringOnItsLineIsRefused()
    {
        var exception = Assert.Throws<PhpSourceException>(() => _codec.Read("<?php\nconst array Names = [\n    'a', // note\n    'b',\n];"));
        Assert.Contains("line of its own", exception.Message);
    }

    [Theory]
    [InlineData("", "Entry 2 is blank")]
    [InlineData("a\nb", "Entry 2 contains a line break")]
    public void ProblemsAreFoundBeforeSaving(string value, string message)
    {
        Assert.Contains(message, _codec.Problem(["fine", value]));
    }
}
