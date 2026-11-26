using System.Text;

namespace PoppySeedPets.Editor.Php;

/// <summary>An item of a <see cref="StringArray"/>: a <c>// heading</c> or a string.</summary>
public sealed record ArrayEntry(bool IsHeading, string Value);

/// <summary>
/// A <c>const array</c> of single-quoted strings, optionally with <c>// heading</c> lines between them, as written.
/// </summary>
/// <remarks>
/// Scanning is strict: anything else in the array is refused, so saving can never drop what it didn't understand.
/// Writing aligns the file's entries with the new ones and only renders entries that are new or changed; everything
/// else, oddities included (stray whitespace, a missing trailing comma), keeps its original bytes. Arrays with several
/// strings to a line stay that way.
/// </remarks>
public sealed class StringArray
{
    private const int MaxPackedLineLength = 120;

    private readonly string _text;
    private readonly Scanned _scan;

    private StringArray(string text, Scanned scan) => (_text, _scan) = (text, scan);

    public IEnumerable<ArrayEntry> Entries => _scan.Entries.Select(e => e.Entry);

    /// <param name="text">The file.</param>
    /// <param name="constName">The const.</param>
    /// <param name="headings">
    /// Whether the array's comments are headings, in which case every string needs one above it. If not, comments are
    /// just part of the file: each stays above the string after it, and goes when the last string on that line does.
    /// </param>
    /// <exception cref="PhpSourceException">The const isn't there, or has something else in it.</exception>
    public static StringArray Scan(string text, string constName, bool headings) =>
        new(text, ScanEntries(text, constName, headings));

    /// <returns>The file, with its array holding <paramref name="target"/>.</returns>
    public string Write(IReadOnlyList<ArrayEntry> target) =>
        string.Concat(_text.AsSpan(0, _scan.BodyStart), Render(_scan, target), _text.AsSpan(_scan.BodyEnd));

    /// <summary>What's stopping a string from being saved, or null if nothing is.</summary>
    public static string? StringProblem(string value) =>
        string.IsNullOrWhiteSpace(value) ? "is blank"
        : value.AsSpan().ContainsAny('\r', '\n') ? "contains a line break"
        : null;

    private static string Render(Scanned scan, IReadOnlyList<ArrayEntry> target)
    {
        var origins = Origins(scan.Entries, target);
        var inherited = InheritedLeadings(scan.Entries, origins);
        var lastString = target.Index().LastOrDefault(e => !e.Item.IsHeading, (-1, null!)).Index;
        var first = scan.Entries.FirstOrDefault();
        var packed = scan.Entries.Skip(1).Any(e => !e.Leading.Contains('\n'));
        var body = new StringBuilder();

        for (var i = 0; i < target.Count; i++)
        {
            var (entry, origin) = (target[i], origins[i]);

            var code = origin?.Entry == entry ? origin.Code
                : entry.IsHeading ? (entry.Value.Length == 0 ? "//" : "// " + entry.Value)
                : PhpStringLiteral.Encode(entry.Value);

            var own = origin is null ? null : inherited.GetValueOrDefault(origin, origin.Leading);

            // Only the list's first entry has no blank line before it, whichever entry that now is.
            string leading;
            if (i == 0) leading = own is not null ? WithoutBlankLines(own) : first is not null ? LastLine(first.Leading, scan) : scan.LineBreak + scan.Indent;
            else if (own is not null && !(entry.IsHeading && ReferenceEquals(origin, first))) leading = own;
            else if (entry.IsHeading) leading = scan.LineBreak + scan.LineBreak + scan.Indent;
            else leading = packed && !target[i - 1].IsHeading && FitsOnLine(body, code) ? " " : scan.LineBreak + scan.Indent;

            // Commas are only added where PHP needs one, or on a new line, which spares the next edit a changed line.
            var comma = entry.IsHeading ? "" : origin?.Comma ?? ",";
            if (comma.Length == 0 && !entry.IsHeading && i != lastString) comma = ",";

            body.Append(leading).Append(code).Append(comma);
        }

        return body.ToString();
    }

    private static string WithoutBlankLines(string leading)
    {
        while (true)
        {
            var lineEnd = leading.IndexOf('\n');
            var nextLineEnd = lineEnd < 0 ? -1 : leading.IndexOf('\n', lineEnd + 1);
            if (nextLineEnd < 0 || !string.IsNullOrWhiteSpace(leading[(lineEnd + 1)..nextLineEnd])) return leading;
            leading = leading[..(lineEnd + 1)] + leading[(nextLineEnd + 1)..];
        }
    }

    /// <summary>Just the line break and indent, without any comments that belong to the entry it came from.</summary>
    private static string LastLine(string leading, Scanned scan)
    {
        var lineEnd = leading.LastIndexOf('\n');
        return lineEnd < 0 ? leading : scan.LineBreak + leading[(lineEnd + 1)..];
    }

    private static bool FitsOnLine(StringBuilder body, string code)
    {
        var lineStart = body.ToString().LastIndexOf('\n') + 1;
        return body.Length - lineStart + " ".Length + code.Length + ",".Length <= MaxPackedLineLength;
    }

    /// <summary>
    /// In a packed array, the entry after a removed line-starter would otherwise join the end of the line above, so it
    /// takes over the start of the line instead. Keyed by reference, as two entries can be equal.
    /// </summary>
    private static Dictionary<SourceEntry, string> InheritedLeadings(IReadOnlyList<SourceEntry> source, SourceEntry?[] origins)
    {
        var inherited = new Dictionary<SourceEntry, string>(ReferenceEqualityComparer.Instance);
        var kept = new HashSet<SourceEntry>(origins.OfType<SourceEntry>(), ReferenceEqualityComparer.Instance);

        string? removedLineStart = null;
        foreach (var entry in source)
        {
            var startsLine = entry.Leading.Contains('\n');
            if (!kept.Contains(entry))
            {
                if (startsLine) removedLineStart ??= entry.Leading;
                continue;
            }

            if (removedLineStart is not null && !startsLine) inherited[entry] = removedLineStart;
            removedLineStart = null;
        }

        return inherited;
    }

    /// <summary>
    /// For each target entry, the source entry whose surroundings it takes: its own, if it's unchanged; otherwise one
    /// of the same kind that was changed or removed in the same place (that's an edit in place); otherwise null.
    /// </summary>
    private static SourceEntry?[] Origins(IReadOnlyList<SourceEntry> source, IReadOnlyList<ArrayEntry> target)
    {
        var origins = new SourceEntry?[target.Count];
        var matches = Align(source, target);

        var sourceAt = 0;
        for (var targetAt = 0; targetAt < target.Count; targetAt++)
        {
            if (matches[targetAt] is { } match)
            {
                origins[targetAt] = source[match];
                sourceAt = match + 1;
                continue;
            }

            var nextMatch = Array.Find(matches[targetAt..], m => m is not null);
            var gapEnd = nextMatch ?? source.Count;

            var replaced = sourceAt;
            while (replaced < gapEnd && source[replaced].Entry.IsHeading != target[targetAt].IsHeading) replaced++;
            if (replaced == gapEnd) continue;

            origins[targetAt] = source[replaced];
            sourceAt = replaced + 1;
        }

        return origins;
    }

    /// <summary>A longest common subsequence.</summary>
    /// <returns>For each target entry, the index of the source entry it's aligned with, if any.</returns>
    private static int?[] Align(IReadOnlyList<SourceEntry> source, IReadOnlyList<ArrayEntry> target)
    {
        var matches = new int?[target.Count];

        // Almost all of the two lists is the same, so trimming the ends leaves next to nothing to compare.
        var start = 0;
        while (start < source.Count && start < target.Count && source[start].Entry == target[start])
        {
            matches[start] = start;
            start++;
        }

        var (sourceEnd, targetEnd) = (source.Count, target.Count);
        while (sourceEnd > start && targetEnd > start && source[sourceEnd - 1].Entry == target[targetEnd - 1])
            matches[--targetEnd] = --sourceEnd;

        var (rows, columns) = (sourceEnd - start, targetEnd - start);
        var lengths = new int[rows + 1, columns + 1];
        for (var row = rows - 1; row >= 0; row--)
            for (var column = columns - 1; column >= 0; column--)
                lengths[row, column] = source[start + row].Entry == target[start + column]
                    ? lengths[row + 1, column + 1] + 1
                    : Math.Max(lengths[row + 1, column], lengths[row, column + 1]);

        var (r, c) = (0, 0);
        while (r < rows && c < columns)
        {
            if (source[start + r].Entry == target[start + c]) matches[start + c++] = start + r++;
            else if (lengths[r + 1, c] >= lengths[r, c + 1]) r++;
            else c++;
        }

        return matches;
    }

    private static Scanned ScanEntries(string text, string constName, bool headings)
    {
        var source = PhpSource.Parse(text);
        var (open, close) = source.FindConstArray(constName);

        var entries = new List<SourceEntry>();
        var bodyStart = source.Tokens[open].End;
        var previousEnd = bodyStart;

        for (var i = open + 1; i < close; i++)
        {
            var token = source.Tokens[i];
            var leading = text[previousEnd..token.Start];
            var code = source.TextOf(token);

            switch (token.Kind)
            {
                case PhpTokenKind.LineComment when headings && code.StartsWith("//", StringComparison.Ordinal):
                    if (entries.Count > 0 && !leading.Contains('\n'))
                        throw Refuse(source, constName, token, "a heading needs a line of its own");

                    var heading = code.AsSpan(2);
                    if (heading.StartsWith(" ")) heading = heading[1..];
                    entries.Add(new(new(true, heading.ToString()), leading, code, ""));
                    previousEnd = token.End;
                    break;

                case PhpTokenKind.LineComment when !headings && code.StartsWith("//", StringComparison.Ordinal):
                    if (entries.Count > 0 && !leading.Contains('\n'))
                        throw Refuse(source, constName, token, "a comment needs a line of its own");

                    break; // previousEnd stays put, which makes it part of the next string's leading text

                case PhpTokenKind.SingleQuotedString:
                    if (headings && entries.Count == 0) throw Refuse(source, constName, token, "a string needs a // heading above it");

                    previousEnd = token.End;
                    var comma = "";
                    if (i + 1 < close)
                    {
                        if (source.TextOf(source.Tokens[i + 1]) != ",") throw Refuse(source, constName, source.Tokens[i + 1], "expected a comma");
                        previousEnd = source.Tokens[++i].End;
                        comma = text[token.End..previousEnd];
                    }

                    entries.Add(new(new(false, PhpStringLiteral.Decode(code)), leading, code, comma));
                    break;

                default:
                    throw Refuse(source, constName, token, "only // comments and single-quoted strings are understood here");
            }
        }

        var lineBreak = text.Contains("\r\n") ? "\r\n" : "\n";
        return new Scanned(bodyStart, previousEnd, entries, lineBreak, IndentOf(text, source.Tokens[close].Start) + "    ");
    }

    /// <summary>The whitespace before <paramref name="offset"/>, if that's all that's before it on its line.</summary>
    private static string IndentOf(string text, int offset)
    {
        var line = text[(text.LastIndexOf('\n', offset) + 1)..offset];
        return string.IsNullOrWhiteSpace(line) ? line : "";
    }

    private static PhpSourceException Refuse(PhpSource source, string constName, PhpToken token, string why)
    {
        var found = source.TextOf(token);
        return new PhpSourceException($"Line {source.LineAt(token.Start)} ({constName}): {why} (found {found[..Math.Min(40, found.Length)]}).");
    }

    /// <param name="Leading">Everything between the previous entry and this one.</param>
    /// <param name="Code">The comment or literal, exactly as written.</param>
    /// <param name="Comma">Exactly as written, including any space before it; empty if there isn't one.</param>
    private sealed record SourceEntry(ArrayEntry Entry, string Leading, string Code, string Comma);

    /// <param name="BodyEnd">The end of the last entry. What's between that and the ] is never touched.</param>
    /// <param name="Indent">For the first entry of an array that has none to copy.</param>
    private sealed record Scanned(int BodyStart, int BodyEnd, IReadOnlyList<SourceEntry> Entries, string LineBreak, string Indent);
}
