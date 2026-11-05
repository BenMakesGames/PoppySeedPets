using System.Collections.Immutable;
using System.Text;

namespace PoppySeedPets.Editor.Php;

/// <summary>A run of strings under a <c>// heading</c>.</summary>
public sealed record StringGroup(string Heading, ImmutableArray<string> Strings)
{
    public bool Equals(StringGroup? other) => other is not null && Heading == other.Heading && Strings.SequenceEqual(other.Strings);

    public override int GetHashCode() => Heading.GetHashCode();
}

/// <summary>
/// A <c>const array</c> of single-quoted strings, grouped by comment headings:
/// <code>
/// const array Name = [
///     // Heading
///     'string',
///     'string',
///
///     // Heading
///     'string'
/// ];
/// </code>
/// Reading is strict: anything else in the array is refused, so saving can never drop what it didn't understand.
/// Writing aligns the file's entries with the new ones and only renders entries that are new or changed; everything
/// else, oddities included (stray whitespace, a missing trailing comma), keeps its original bytes.
/// </summary>
public sealed class GroupedStringsCodec(string constName) : IPhpCodec<IReadOnlyList<StringGroup>>
{
    public IReadOnlyList<StringGroup> Read(string source)
    {
        var groups = new List<StringGroup>();
        foreach (var (entry, _, _, _) in Scan(source).Entries)
        {
            if (entry.IsHeading) groups.Add(new StringGroup(entry.Value, []));
            else groups[^1] = groups[^1] with { Strings = groups[^1].Strings.Add(entry.Value) };
        }

        return groups;
    }

    public string? Problem(IReadOnlyList<StringGroup> groups)
    {
        foreach (var (index, group) in groups.Index())
        {
            if (string.IsNullOrWhiteSpace(group.Heading)) return $"Group {index + 1} has no heading.";

            // A line break would end the comment, and ?> would end PHP mode, even inside a // comment.
            if (group.Heading.AsSpan().ContainsAny('\r', '\n') || group.Heading.Contains("?>"))
                return $"Group {index + 1}'s heading can't contain a line break or ?>.";

            foreach (var (line, value) in group.Strings.Index())
            {
                if (string.IsNullOrWhiteSpace(value)) return $"\"{group.Heading}\": line {line + 1} is blank.";
                if (value.AsSpan().ContainsAny('\r', '\n')) return $"\"{group.Heading}\": line {line + 1} contains a line break.";
            }
        }

        return null;
    }

    public string Write(string source, IReadOnlyList<StringGroup> groups)
    {
        var scan = Scan(source);
        var target = groups
            .SelectMany(g => g.Strings.Select(s => new Entry(false, s)).Prepend(new Entry(true, g.Heading)))
            .ToList();

        var result = string.Concat(source.AsSpan(0, scan.BodyStart), Render(scan, target), source.AsSpan(scan.BodyEnd));

        // The last line of defense: never write what wouldn't read back.
        if (!Read(result).SequenceEqual(groups))
            throw new PhpSourceException($"Saving would have corrupted {constName}, so nothing was written.");

        return result;
    }

    private static string Render(Scanned scan, List<Entry> target)
    {
        var origins = Origins(scan.Entries, target);
        var lastString = target.FindLastIndex(e => !e.IsHeading);
        var first = scan.Entries.FirstOrDefault();
        var body = new StringBuilder();

        for (var i = 0; i < target.Count; i++)
        {
            var (entry, origin) = (target[i], origins[i]);

            // Only the list's first entry has no blank line before it, whichever entry that now is.
            var leading = i == 0
                ? first?.Leading ?? scan.LineBreak + scan.Indent
                : origin is null || origin == first
                    ? (entry.IsHeading ? scan.LineBreak : "") + scan.LineBreak + scan.Indent
                    : origin.Leading;

            var code = origin?.Entry == entry ? origin.Code
                : entry.IsHeading ? (entry.Value.Length == 0 ? "//" : "// " + entry.Value)
                : PhpStringLiteral.Encode(entry.Value);

            // Commas are only added where PHP needs one, or on a new line, which spares the next edit a changed line.
            var comma = entry.IsHeading ? "" : origin?.Comma ?? ",";
            if (comma.Length == 0 && !entry.IsHeading && i != lastString) comma = ",";

            body.Append(leading).Append(code).Append(comma);
        }

        return body.ToString();
    }

    /// <summary>
    /// For each target entry, the source entry whose surroundings it takes: its own, if it's unchanged; otherwise one
    /// of the same kind that was changed or removed in the same place (that's an edit in place); otherwise null.
    /// </summary>
    private static SourceEntry?[] Origins(IReadOnlyList<SourceEntry> source, List<Entry> target)
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
    private static int?[] Align(IReadOnlyList<SourceEntry> source, List<Entry> target)
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

    private Scanned Scan(string text)
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
                case PhpTokenKind.LineComment when code.StartsWith("//", StringComparison.Ordinal):
                    if (entries.Count > 0 && !leading.Contains('\n'))
                        throw Refuse(source, token, "a heading needs a line of its own");

                    var heading = code.AsSpan(2);
                    if (heading.StartsWith(" ")) heading = heading[1..];
                    entries.Add(new(new(true, heading.ToString()), leading, code, ""));
                    previousEnd = token.End;
                    break;

                case PhpTokenKind.SingleQuotedString:
                    if (entries.Count == 0) throw Refuse(source, token, "a string needs a // heading above it");

                    previousEnd = token.End;
                    var comma = "";
                    if (i + 1 < close)
                    {
                        if (source.TextOf(source.Tokens[i + 1]) != ",") throw Refuse(source, source.Tokens[i + 1], "expected a comma");
                        previousEnd = source.Tokens[++i].End;
                        comma = text[token.End..previousEnd];
                    }

                    entries.Add(new(new(false, PhpStringLiteral.Decode(code)), leading, code, comma));
                    break;

                default:
                    throw Refuse(source, token, "only // headings and single-quoted strings are understood here");
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

    private PhpSourceException Refuse(PhpSource source, PhpToken token, string why)
    {
        var found = source.TextOf(token);
        return new PhpSourceException($"Line {source.LineAt(token.Start)} ({constName}): {why} (found {found[..Math.Min(40, found.Length)]}).");
    }

    private sealed record Entry(bool IsHeading, string Value);

    /// <param name="Leading">Everything between the previous entry and this one.</param>
    /// <param name="Code">The comment or literal, exactly as written.</param>
    /// <param name="Comma">Exactly as written, including any space before it; empty if there isn't one.</param>
    private sealed record SourceEntry(Entry Entry, string Leading, string Code, string Comma);

    /// <param name="BodyEnd">The end of the last entry. What's between that and the ] is never touched.</param>
    /// <param name="Indent">For the first entry of an array that has none to copy.</param>
    private sealed record Scanned(int BodyStart, int BodyEnd, IReadOnlyList<SourceEntry> Entries, string LineBreak, string Indent);
}
