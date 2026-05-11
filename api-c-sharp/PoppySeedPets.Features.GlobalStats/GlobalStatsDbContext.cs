using Microsoft.EntityFrameworkCore;

namespace PoppySeedPets.Features.GlobalStats;

// Database-first: Doctrine owns the schema. Never call EnsureCreated() or Add-Migration.
// Entity → table mapping lives on the entities via [Table]/[Column]/[Key] attributes.
// For anything the attribute model can't express (composite keys, value converters,
// owned types, computed columns, etc.), add a nested `internal sealed class Configuration
// : IEntityTypeConfiguration<TEntity>` on the entity itself — ApplyConfigurationsFromAssembly
// picks it up automatically.
public sealed class GlobalStatsDbContext(DbContextOptions<GlobalStatsDbContext> options) : DbContext(options)
{
    public DbSet<DailyStats> DailyStats => Set<DailyStats>();

    protected override void OnModelCreating(ModelBuilder modelBuilder)
    {
        modelBuilder.ApplyConfigurationsFromAssembly(typeof(GlobalStatsDbContext).Assembly);
    }
}
