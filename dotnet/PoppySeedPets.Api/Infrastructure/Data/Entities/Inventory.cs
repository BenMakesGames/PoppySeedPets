namespace PoppySeedPets.Api.Infrastructure.Data.Entities;

/// <summary>
/// One item a player holds. Player (mutable) data.
///
/// <see cref="ItemId"/> is a bare FK to the item definition — intentionally NOT an EF navigation
/// property. Definitions are served from the in-memory store, not via ORM navigation, so the hot
/// read path can never accidentally join definition data (reference-data-and-caching.md, D2).
/// </summary>
public class Inventory
{
    public int Id { get; set; }

    public int OwnerId { get; set; }

    public int ItemId { get; set; }

    public int Location { get; set; }

    public string? EnchantmentName { get; set; }
}
