using Microsoft.EntityFrameworkCore;
using PoppySeedPets.Api.Infrastructure.Data.Entities;

namespace PoppySeedPets.Api.Infrastructure.Data;

public class PspDbContext : DbContext
{
    public PspDbContext(DbContextOptions<PspDbContext> options) : base(options) { }

    public DbSet<User> Users => Set<User>();
    public DbSet<UserSession> UserSessions => Set<UserSession>();
    public DbSet<Item> Items => Set<Item>();
    public DbSet<Inventory> Inventory => Set<Inventory>();

    protected override void OnModelCreating(ModelBuilder b)
    {
        b.Entity<User>(e =>
        {
            e.ToTable("user");
            e.HasKey(x => x.Id);
            e.HasIndex(x => x.Email).IsUnique();
        });

        b.Entity<UserSession>(e =>
        {
            e.ToTable("user_session");
            e.HasKey(x => x.Id);
            e.HasIndex(x => x.SessionIdHash).IsUnique();
            e.HasOne(x => x.User).WithMany().HasForeignKey(x => x.UserId);
        });

        b.Entity<Item>(e =>
        {
            e.ToTable("item");
            e.HasKey(x => x.Id);
        });

        b.Entity<Inventory>(e =>
        {
            e.ToTable("inventory");
            e.HasKey(x => x.Id);
            e.HasIndex(x => x.OwnerId);
            // Deliberately no relationship to Item — ItemId is a bare FK (D2).
        });
    }
}
