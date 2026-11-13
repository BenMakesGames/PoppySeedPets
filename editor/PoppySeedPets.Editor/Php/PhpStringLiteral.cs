using System.Text;

namespace PoppySeedPets.Editor.Php;

/// <summary>PHP single-quoted strings, where only <c>\'</c> and <c>\\</c> are escapes: every other backslash is itself.</summary>
public static class PhpStringLiteral
{
    /// <param name="literal">The literal's source text, quotes included.</param>
    public static string Decode(string literal)
    {
        var body = literal.AsSpan(1, literal.Length - 2);
        var value = new StringBuilder(body.Length);
        for (var i = 0; i < body.Length; i++)
        {
            if (body[i] == '\\' && i + 1 < body.Length && body[i + 1] is '\'' or '\\') i++;
            value.Append(body[i]);
        }

        return value.ToString();
    }

    /// <summary>Escapes only what has to be, so that a harmless <c>\*</c> stays <c>\*</c>.</summary>
    public static string Encode(string value)
    {
        var literal = new StringBuilder(value.Length + 2).Append('\'');
        for (var i = 0; i < value.Length; i++)
        {
            var c = value[i];
            if (c == '\'' || (c == '\\' && (i + 1 == value.Length || value[i + 1] is '\'' or '\\'))) literal.Append('\\');
            literal.Append(c);
        }

        return literal.Append('\'').ToString();
    }
}
