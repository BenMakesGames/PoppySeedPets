namespace PoppySeedPets.Editor.Php;

/// <summary>A <see cref="StringArray"/> with no headings: <c>const array Name = [ 'string', 'string' ];</c></summary>
public sealed class StringListCodec(string constName) : IPhpCodec<IReadOnlyList<string>>
{
    public IReadOnlyList<string> Read(string source) =>
        StringArray.Scan(source, constName, headings: false).Entries.Select(e => e.Value).ToList();

    public string? Problem(IReadOnlyList<string> strings)
    {
        foreach (var (index, value) in strings.Index())
            if (StringArray.StringProblem(value) is { } problem)
                return $"Entry {index + 1} {problem}.";

        return null;
    }

    public string Write(string source, IReadOnlyList<string> strings)
    {
        var target = strings.Select(s => new ArrayEntry(false, s)).ToList();
        var result = StringArray.Scan(source, constName, headings: false).Write(target);

        // The last line of defense: never write what wouldn't read back.
        if (!Read(result).SequenceEqual(strings))
            throw new PhpSourceException($"Saving would have corrupted {constName}, so nothing was written.");

        return result;
    }
}
