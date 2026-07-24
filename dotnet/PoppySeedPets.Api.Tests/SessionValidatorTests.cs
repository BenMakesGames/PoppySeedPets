using Microsoft.Data.Sqlite;
using Microsoft.EntityFrameworkCore;
using PoppySeedPets.Api.Infrastructure.Auth;
using PoppySeedPets.Api.Infrastructure.Data;
using PoppySeedPets.Api.Infrastructure.Data.Entities;

namespace PoppySeedPets.Api.Tests;

public sealed class SessionValidatorTests : IDisposable
{
    private readonly SqliteConnection _connection;
    private readonly DbContextOptions<PspDbContext> _options;

    public SessionValidatorTests()
    {
        // In-memory SQLite: the DB lives as long as the connection is open.
        _connection = new SqliteConnection("DataSource=:memory:");
        _connection.Open();
        _options = new DbContextOptionsBuilder<PspDbContext>().UseSqlite(_connection).Options;
        using var ctx = new PspDbContext(_options);
        ctx.Database.EnsureCreated();
    }

    private PspDbContext NewContext() => new(_options);

    private sealed class FixedTimeProvider(DateTimeOffset now) : TimeProvider
    {
        public override DateTimeOffset GetUtcNow() => now;
    }

    private static readonly DateTimeOffset Now = new(2026, 1, 1, 0, 0, 0, TimeSpan.Zero);

    private string SeedSession(DateTimeOffset expiration, bool locked = false)
    {
        using var ctx = NewContext();
        var user = new User { Email = "p@p.com", PassphraseHash = "x", IsLocked = locked, SessionLengthHours = 24 };
        var token = SessionTokens.Generate();
        ctx.Users.Add(user);
        ctx.UserSessions.Add(new UserSession
        {
            SessionIdHash = SessionTokens.Hash(token),
            User = user,
            SessionExpiration = expiration,
        });
        ctx.SaveChanges();
        return token;
    }

    [Fact]
    public async Task Valid_token_authenticates_and_slides_expiry()
    {
        var token = SeedSession(Now.AddHours(1));

        SessionValidationResult result;
        await using (var ctx = NewContext())
            result = await new SessionValidator(ctx, new FixedTimeProvider(Now)).ValidateAsync(token);

        Assert.Equal(SessionStatus.Valid, result.Status);
        Assert.Equal("p@p.com", result.User!.Email);

        await using (var ctx = NewContext())
            Assert.Equal(Now.AddHours(24), ctx.UserSessions.Single().SessionExpiration);
    }

    [Fact]
    public async Task Expired_session_is_rejected()
    {
        var token = SeedSession(Now.AddHours(-1));

        await using var ctx = NewContext();
        var result = await new SessionValidator(ctx, new FixedTimeProvider(Now)).ValidateAsync(token);

        Assert.Equal(SessionStatus.Expired, result.Status);
    }

    [Fact]
    public async Task Locked_user_is_rejected()
    {
        var token = SeedSession(Now.AddHours(1), locked: true);

        await using var ctx = NewContext();
        var result = await new SessionValidator(ctx, new FixedTimeProvider(Now)).ValidateAsync(token);

        Assert.Equal(SessionStatus.Locked, result.Status);
    }

    [Fact]
    public async Task Unknown_token_is_not_found()
    {
        SeedSession(Now.AddHours(1));

        await using var ctx = NewContext();
        var result = await new SessionValidator(ctx, new FixedTimeProvider(Now)).ValidateAsync(SessionTokens.Generate());

        Assert.Equal(SessionStatus.NotFound, result.Status);
    }

    [Fact]
    public void Only_the_hash_is_persisted_never_the_plaintext_token()
    {
        var token = SeedSession(Now.AddHours(1));

        using var ctx = NewContext();
        var stored = ctx.UserSessions.Single().SessionIdHash;
        Assert.NotEqual(token, stored);
        Assert.Equal(SessionTokens.Hash(token), stored);
    }

    public void Dispose() => _connection.Dispose();
}
