namespace PoppySeedPets.Api.Infrastructure.Data.Entities;

/// <summary>
/// An item *definition* — version-locked reference data shipped in the seed.
///
/// It is EF-mapped so the startup load and the few attribute-filtering search endpoints can
/// query it in SQL (reference-data-and-caching.md, Option D2). But player rows deliberately do
/// NOT navigate to it — they carry a bare <c>ItemId</c> and stitch the definition from the
/// in-memory <see cref="ReferenceData.IItemDefinitions"/> store instead. That keeps the hot path
/// from ever joining definition data.
/// </summary>
public class Item
{
    public int Id { get; set; }

    public string Name { get; set; } = "";

    public string? Image { get; set; }

    // Simplification: in the real schema food-ness lives in the item_food satellite table.
    // Kept as a flag here for the skeleton; the real load will project from the join.
    public bool IsFood { get; set; }
}
