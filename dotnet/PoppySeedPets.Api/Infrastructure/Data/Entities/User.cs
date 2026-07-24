namespace PoppySeedPets.Api.Infrastructure.Data.Entities;

/// <summary>
/// A player account. Player (mutable) data — lives in the DB and is EF-tracked.
/// </summary>
public class User
{
    public int Id { get; set; }

    public string Email { get; set; } = "";

    /// <summary>argon2i PHC-string hash (verified only at login; see auth-cutover.md).</summary>
    public string PassphraseHash { get; set; } = "";

    public bool IsLocked { get; set; }

    public DateTimeOffset? LastActivity { get; set; }

    /// <summary>How long a session lives from each touch. PHP: <c>User::getDefaultSessionLengthInHours()</c>.</summary>
    public int SessionLengthHours { get; set; } = 24 * 30;
}
