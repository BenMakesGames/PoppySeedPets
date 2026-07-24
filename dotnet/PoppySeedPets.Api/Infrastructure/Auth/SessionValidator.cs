using Microsoft.EntityFrameworkCore;
using PoppySeedPets.Api.Infrastructure.Data;
using PoppySeedPets.Api.Infrastructure.Data.Entities;

namespace PoppySeedPets.Api.Infrastructure.Auth;

public enum SessionStatus
{
    Valid,
    NotFound,
    Expired,
    Locked,
}

public sealed record SessionValidationResult(SessionStatus Status, User? User)
{
    public static readonly SessionValidationResult NotFound = new(SessionStatus.NotFound, null);
    public static readonly SessionValidationResult Expired = new(SessionStatus.Expired, null);
    public static SessionValidationResult Locked(User user) => new(SessionStatus.Locked, user);
    public static SessionValidationResult Valid(User user) => new(SessionStatus.Valid, user);
}

public interface ISessionValidator
{
    Task<SessionValidationResult> ValidateAsync(string token, CancellationToken ct = default);
}

/// <summary>
/// The testable core of authentication: turn a raw token into a user (or a failure reason). Kept out
/// of the <see cref="SessionAuthenticationHandler"/> so it can be unit-tested against an in-memory DB
/// without the ASP.NET auth plumbing.
/// </summary>
public sealed class SessionValidator : ISessionValidator
{
    private readonly PspDbContext _db;
    private readonly TimeProvider _clock;

    public SessionValidator(PspDbContext db, TimeProvider clock)
    {
        _db = db;
        _clock = clock;
    }

    public async Task<SessionValidationResult> ValidateAsync(string token, CancellationToken ct = default)
    {
        var hash = SessionTokens.Hash(token);

        var session = await _db.UserSessions
            .Include(s => s.User)
            .SingleOrDefaultAsync(s => s.SessionIdHash == hash, ct);

        if (session is null)
            return SessionValidationResult.NotFound;

        var now = _clock.GetUtcNow();

        if (session.SessionExpiration < now)
            return SessionValidationResult.Expired;

        var user = session.User;

        if (user.IsLocked)
            return SessionValidationResult.Locked(user);

        // Slide the expiry and stamp activity. v1 keeps PHP's write-on-every-request behavior;
        // debouncing this is a deliberate later optimization (auth-cutover.md §4).
        session.SessionExpiration = now.AddHours(user.SessionLengthHours);
        user.LastActivity = now;
        await _db.SaveChangesAsync(ct);

        return SessionValidationResult.Valid(user);
    }
}
