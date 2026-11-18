using PoppySeedPets.Editor.Php;

namespace PoppySeedPets.Editor.Tests;

public sealed class PhpSourceTests
{
    [Fact]
    public void FindsTheConstNotThingsThatLookLikeIt()
    {
        const string text = """
            <?php
            // const array Lyrics = ['in a comment'];
            /* const array Lyrics = ['in a block comment']; */
            #[Attribute]
            class Music
            {
                const string Decoy = 'const array Lyrics = [';
                const string Other = "const array Lyrics = [";
                const array Lyrics = ['a', ['nested'], 'b'];
                const array Rest = ['c'];
            }
            """;

        var source = PhpSource.Parse(text);
        var (open, close) = source.FindConstArray("Lyrics");

        Assert.Equal("['a', ['nested'], 'b']", text[source.Tokens[open].Start..source.Tokens[close].End]);
    }

    [Theory]
    [InlineData("<?php const array Lyrics = 3;", "isn't a [ ... ] array")]
    [InlineData("<?php const array Lyrics = ['a'", "never closed")]
    [InlineData("<?php const array Nope = [];", "Couldn't find")]
    [InlineData("<?php const array Lyrics = []; const array Lyrics = [];", "more than one")]
    public void ConstProblemsAreRefused(string text, string message)
    {
        var exception = Assert.Throws<PhpSourceException>(() => PhpSource.Parse(text).FindConstArray("Lyrics"));
        Assert.Contains(message, exception.Message);
    }

    [Theory]
    [InlineData("<html>", "start with")]
    [InlineData("<?php\n\n'never closed", "Line 3: unterminated string")]
    [InlineData("<?php /* never closed", "unterminated comment")]
    [InlineData("<?php $a = <<<EOT\nit's\nEOT;", "heredocs")]
    [InlineData("<?php ?> it's HTML now", "heredocs")]
    public void WhatCantBeTokenizedIsRefused(string text, string message)
    {
        var exception = Assert.Throws<PhpSourceException>(() => PhpSource.Parse(text));
        Assert.Contains(message, exception.Message);
    }

    [Theory]
    [InlineData("""'plain'""", "plain")]
    [InlineData("""'it\'s'""", "it's")]
    [InlineData("""'\*random bagpipe\*'""", """\*random bagpipe\*""")]
    [InlineData("""'\\\''""", """\'""")]
    [InlineData("""'ends with \\'""", """ends with \""")]
    public void StringLiteralsDecodeLikePhpAndEncodeBackUnchanged(string literal, string value)
    {
        Assert.Equal(value, PhpStringLiteral.Decode(literal));
        Assert.Equal(literal, PhpStringLiteral.Encode(value));
    }

    [Fact]
    public void AnUnnecessarilyEscapedBackslashDecodesToOne()
    {
        Assert.Equal("""back\slash""", PhpStringLiteral.Decode("""'back\\slash'"""));
    }
}
