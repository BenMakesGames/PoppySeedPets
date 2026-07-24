namespace PoppySeedPets.Api.Infrastructure.ReferenceData;

/// <summary>
/// Immutable, version-locked item definition, held in memory for the lifetime of the process.
/// A bespoke projection of the <c>item</c> table — not the EF entity — so the store carries only
/// what reads actually need (reference-data-and-caching.md, Option D).
/// </summary>
public sealed record ItemDefinition(int Id, string Name, string? Image, bool IsFood);
