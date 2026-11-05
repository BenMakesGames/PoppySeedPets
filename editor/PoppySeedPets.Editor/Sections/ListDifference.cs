namespace PoppySeedPets.Editor.Sections;

public static class ListDifference
{
    /// <returns>The first index at which the lists differ, or null if they don't.</returns>
    public static int? First<T>(IReadOnlyList<T> a, IReadOnlyList<T> b)
    {
        for (var i = 0; i < Math.Min(a.Count, b.Count); i++)
            if (!EqualityComparer<T>.Default.Equals(a[i], b[i])) return i;

        return a.Count == b.Count ? null : Math.Min(a.Count, b.Count);
    }
}
