using System.Globalization;

namespace PoppySeedPets.Editor.Sections.StringList;

/// <summary>
/// The order of the name lists: ignoring case and accents, so that Adélina sorts with the Ads, and Ædgyth with the Aes.
/// </summary>
public static class Alphabetical
{
    private static readonly CompareInfo Culture = CultureInfo.InvariantCulture.CompareInfo;

    /// <summary>The same strings in order, blanks last: a line that's just been added stays where it was put.</summary>
    public static IReadOnlyList<string> Sorted(IReadOnlyList<string> strings) => strings
        .OrderBy(string.IsNullOrWhiteSpace)
        .ThenBy(s => s, Comparer<string>.Create((a, b) => Culture.Compare(a, b, CompareOptions.IgnoreCase | CompareOptions.IgnoreNonSpace)))
        .ToList();
}
