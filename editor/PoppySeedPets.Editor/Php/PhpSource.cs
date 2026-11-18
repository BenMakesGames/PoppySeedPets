namespace PoppySeedPets.Editor.Php;

public enum PhpTokenKind
{
    /// <summary>An identifier, keyword or number.</summary>
    Word,

    /// <summary><c>// ...</c> or <c># ...</c>, not including the line break.</summary>
    LineComment,

    BlockComment,
    SingleQuotedString,
    DoubleQuotedString,
    Symbol,
}

public readonly record struct PhpToken(PhpTokenKind Kind, int Start, int End);

/// <summary>
/// A PHP file's tokens, as offsets into its text. Whitespace isn't a token, but nothing is lost: it's still there in
/// <see cref="Text"/>, which is how codecs leave what they didn't change untouched.
/// </summary>
/// <remarks>
/// Only knows enough PHP to never mistake what's inside a string or comment for code. Anything it can't do that for
/// (heredocs, leaving PHP mode with <c>?&gt;</c>) is refused, not guessed at.
/// </remarks>
public sealed class PhpSource
{
    private PhpSource(string text, IReadOnlyList<PhpToken> tokens) => (Text, Tokens) = (text, tokens);

    public string Text { get; }
    public IReadOnlyList<PhpToken> Tokens { get; }

    public string TextOf(PhpToken token) => Text[token.Start..token.End];

    /// <summary>1-based, for messages.</summary>
    public int LineAt(int offset) => LineAt(Text, offset);

    /// <exception cref="PhpSourceException">The file can't be tokenized.</exception>
    public static PhpSource Parse(string text)
    {
        const string openTag = "<?php";
        if (!text.StartsWith(openTag, StringComparison.Ordinal))
            throw new PhpSourceException($"Expected the file to start with {openTag}.");

        var tokens = new List<PhpToken>();
        var i = openTag.Length;
        while (i < text.Length)
        {
            var c = text[i];
            if (char.IsWhiteSpace(c))
            {
                i++;
                continue;
            }

            var next = i + 1 < text.Length ? text[i + 1] : '\0';
            PhpToken token;
            if ((c == '/' && next == '/') || (c == '#' && next != '['))
                token = new(PhpTokenKind.LineComment, i, EndOfLine(text, i));
            else if (c == '/' && next == '*')
                token = new(PhpTokenKind.BlockComment, i, EndOfBlockComment(text, i));
            else if (c == '\'')
                token = new(PhpTokenKind.SingleQuotedString, i, EndOfString(text, i));
            else if (c == '"')
                token = new(PhpTokenKind.DoubleQuotedString, i, EndOfString(text, i));
            else if (IsWordChar(c))
                token = new(PhpTokenKind.Word, i, EndOfWord(text, i));
            else if (text.AsSpan(i).StartsWith("<<<") || text.AsSpan(i).StartsWith("?>"))
                throw new PhpSourceException($"Line {LineAt(text, i)}: heredocs and ?> aren't supported.");
            else
                token = new(PhpTokenKind.Symbol, i, i + 1);

            tokens.Add(token);
            i = token.End;
        }

        return new PhpSource(text, tokens);
    }

    /// <summary>Finds <c>const [type] NAME = [ ... ];</c>, in any class in the file.</summary>
    /// <returns>Indexes into <see cref="Tokens"/> of the brackets.</returns>
    /// <exception cref="PhpSourceException">There isn't exactly one such const.</exception>
    public (int Open, int Close) FindConstArray(string name)
    {
        var matches = new List<int>();
        for (var i = 0; i < Tokens.Count; i++)
        {
            if (!IsKeyword(i, "const")) continue;

            var equals = i + 1;
            while (equals < Tokens.Count && !IsSymbol(equals, '=') && !IsSymbol(equals, ';')) equals++;

            if (equals < Tokens.Count && IsSymbol(equals, '=') && TextOf(Tokens[equals - 1]) == name)
                matches.Add(equals);
        }

        if (matches is not [var match])
            throw new PhpSourceException(matches.Count == 0 ? $"Couldn't find const {name}." : $"Found more than one const {name}.");

        var open = match + 1;
        if (open >= Tokens.Count || !IsSymbol(open, '['))
            throw new PhpSourceException($"Line {LineAt(Tokens[match].Start)}: const {name} isn't a [ ... ] array.");

        var depth = 0;
        for (var i = open; i < Tokens.Count; i++)
        {
            if (IsSymbol(i, '[')) depth++;
            else if (IsSymbol(i, ']') && --depth == 0) return (open, i);
        }

        throw new PhpSourceException($"Line {LineAt(Tokens[open].Start)}: const {name}'s [ is never closed.");
    }

    private bool IsSymbol(int index, char symbol) =>
        Tokens[index] is { Kind: PhpTokenKind.Symbol } token && Text[token.Start] == symbol;

    private bool IsKeyword(int index, string keyword) =>
        Tokens[index] is { Kind: PhpTokenKind.Word } token && TextOf(token).Equals(keyword, StringComparison.OrdinalIgnoreCase);

    private static int LineAt(string text, int offset) => text.AsSpan(0, offset).Count('\n') + 1;

    private static bool IsWordChar(char c) => char.IsLetterOrDigit(c) || c is '_' or '\\' || c >= 0x80;

    private static int EndOfWord(string text, int start)
    {
        var i = start;
        while (i < text.Length && IsWordChar(text[i])) i++;
        return i;
    }

    private static int EndOfLine(string text, int start)
    {
        var found = text.AsSpan(start).IndexOfAny('\r', '\n');
        return found < 0 ? text.Length : start + found;
    }

    private static int EndOfBlockComment(string text, int start)
    {
        var close = text.IndexOf("*/", start + 2, StringComparison.Ordinal);
        return close >= 0 ? close + 2 : throw new PhpSourceException($"Line {LineAt(text, start)}: unterminated comment.");
    }

    private static int EndOfString(string text, int start)
    {
        for (var i = start + 1; i < text.Length; i++)
        {
            if (text[i] == '\\') i++;
            else if (text[i] == text[start]) return i + 1;
        }

        throw new PhpSourceException($"Line {LineAt(text, start)}: unterminated string.");
    }
}
