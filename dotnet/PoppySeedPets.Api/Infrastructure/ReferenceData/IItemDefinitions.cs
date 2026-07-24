namespace PoppySeedPets.Api.Infrastructure.ReferenceData;

/// <summary>
/// Read-only, in-memory lookup of item definitions. Populated once at startup; never changes at
/// runtime (definitions are locked to the app version). See reference-data-and-caching.md.
/// </summary>
public interface IItemDefinitions
{
    /// <summary>
    /// Look up a definition by id. Throws if absent: definitions are version-locked, so a missing id
    /// is a bug (bad FK / stale client), not an expected miss — fail loudly rather than null-stitch.
    /// </summary>
    ItemDefinition this[int id] { get; }

    bool TryGet(int id, out ItemDefinition definition);

    IReadOnlyCollection<ItemDefinition> All { get; }

    /// <summary>
    /// Ids matching a predicate — the app-side half of the "id-set-IN" fallback for search endpoints
    /// that can't express a predicate in SQL (reference-data-and-caching.md §5.1). Bounded by table size.
    /// </summary>
    IReadOnlyList<int> IdsWhere(Func<ItemDefinition, bool> predicate);

    int Count { get; }
}
