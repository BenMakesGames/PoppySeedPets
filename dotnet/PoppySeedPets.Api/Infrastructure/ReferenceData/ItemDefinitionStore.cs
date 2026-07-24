using System.Collections.Frozen;
using Microsoft.EntityFrameworkCore;
using PoppySeedPets.Api.Infrastructure.Data;

namespace PoppySeedPets.Api.Infrastructure.ReferenceData;

/// <summary>
/// <see cref="FrozenDictionary{TKey,TValue}"/>-backed reference store. Built once at startup and
/// then read-only — no TTL, no invalidation, no Redis. A restart (i.e. a deploy, the only time the
/// data changes) is the sole "cache refresh". <see cref="FrozenDictionary{TKey,TValue}"/> is
/// purpose-built for exactly this "build once, read forever" shape.
/// </summary>
public sealed class ItemDefinitionStore : IItemDefinitions
{
    private FrozenDictionary<int, ItemDefinition> _byId = FrozenDictionary<int, ItemDefinition>.Empty;

    /// <summary>Replace the contents (used by the startup loader and by tests).</summary>
    public void Load(IEnumerable<ItemDefinition> items) => _byId = items.ToFrozenDictionary(i => i.Id);

    /// <summary>Project the definition columns straight out of the <c>item</c> table into memory.</summary>
    public async Task LoadFromDatabaseAsync(PspDbContext db, CancellationToken ct = default)
    {
        var items = await db.Items
            .AsNoTracking()
            .Select(i => new ItemDefinition(i.Id, i.Name, i.Image, i.IsFood))
            .ToListAsync(ct);

        Load(items);
    }

    public ItemDefinition this[int id] =>
        _byId.TryGetValue(id, out var def)
            ? def
            : throw new KeyNotFoundException(
                $"No item definition #{id}. Definitions are version-locked; this id is not in the shipped seed.");

    public bool TryGet(int id, out ItemDefinition definition) => _byId.TryGetValue(id, out definition!);

    public IReadOnlyCollection<ItemDefinition> All => _byId.Values;

    public IReadOnlyList<int> IdsWhere(Func<ItemDefinition, bool> predicate) =>
        _byId.Values.Where(predicate).Select(i => i.Id).ToArray();

    public int Count => _byId.Count;
}
