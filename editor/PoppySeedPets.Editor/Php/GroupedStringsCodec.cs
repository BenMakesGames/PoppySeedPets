using System.Collections.Immutable;

namespace PoppySeedPets.Editor.Php;

/// <summary>A run of strings under a <c>// heading</c>.</summary>
public sealed record StringGroup(string Heading, ImmutableArray<string> Strings)
{
    public bool Equals(StringGroup? other) => other is not null && Heading == other.Heading && Strings.SequenceEqual(other.Strings);

    public override int GetHashCode() => Heading.GetHashCode();
}

/// <summary>
/// A <see cref="StringArray"/> grouped by comment headings:
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
/// </summary>
public sealed class GroupedStringsCodec(string constName) : IPhpCodec<IReadOnlyList<StringGroup>>
{
    public IReadOnlyList<StringGroup> Read(string source)
    {
        var groups = new List<StringGroup>();
        foreach (var entry in StringArray.Scan(source, constName, headings: true).Entries)
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
                if (StringArray.StringProblem(value) is { } problem)
                    return $"\"{group.Heading}\": line {line + 1} {problem}.";
        }

        return null;
    }

    public string Write(string source, IReadOnlyList<StringGroup> groups)
    {
        var target = groups
            .SelectMany(g => g.Strings.Select(s => new ArrayEntry(false, s)).Prepend(new ArrayEntry(true, g.Heading)))
            .ToList();

        var result = StringArray.Scan(source, constName, headings: true).Write(target);

        // The last line of defense: never write what wouldn't read back.
        if (!Read(result).SequenceEqual(groups))
            throw new PhpSourceException($"Saving would have corrupted {constName}, so nothing was written.");

        return result;
    }
}
